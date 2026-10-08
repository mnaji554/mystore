<?php

namespace App\Services;

use App\Contracts\ProductSearch;
use Illuminate\Database\Eloquent\Builder;

class DatabaseProductSearch implements ProductSearch
{
    public function apply(Builder $query, string $term): Builder
    {
        $term = trim(mb_scrub($term, 'UTF-8'));

        if ($term === '') {
            return $query;
        }

        $words = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [$term];

        foreach (array_slice($words, 0, 6) as $word) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('products.name_ar', 'like', $like)
                    ->orWhere('products.name_en', 'like', $like)
                    ->orWhere('products.sku', 'like', $like)
                    ->orWhere('products.short_description', 'like', $like)
                    ->orWhere('products.description', 'like', $like)
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name_ar', 'like', $like)->orWhere('name_en', 'like', $like))
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', $like));
            });
        }

        $exact = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        // name matches rank before description-only matches
        return $query->orderByRaw(
            'CASE WHEN products.name_ar LIKE ? OR products.name_en LIKE ? OR products.sku LIKE ? THEN 0 ELSE 1 END',
            [$exact, $exact, $exact]
        );
    }
}
