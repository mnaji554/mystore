<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{
    /**
     * Active category tree (roots with nested children). Only a flat array of light rows is cached
     * (hundreds of categories as full models would exceed MySQL's max_allowed_packet).
     */
    public function tree(): Collection
    {
        $rows = Cache::remember('categories.rows', 3600, fn () => Category::query()->active()
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'parent_id', 'name_ar', 'name_en', 'slug', 'image', 'sort_order'])
            ->map(fn ($c) => $c->getAttributes())->all());

        $byParent = collect($rows)->groupBy(fn ($r) => $r['parent_id'] ?? 0);

        $build = function ($parentId) use (&$build, $byParent) {
            return Category::hydrate($byParent->get($parentId ?? 0, collect())->all())
                ->each(fn (Category $c) => $c->setRelation('children', $build($c->id)));
        };

        return $build(null);
    }

    public function findActiveBySlug(string $slug): ?Category
    {
        return Category::query()->active()->with(['parent', 'children' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)->first();
    }

    /** Flat list ordered as a tree, with depth, for admin selects. */
    public function flat(?int $excludeId = null): array
    {
        $all = Category::query()->orderBy('sort_order')->orderBy('id')->get()->groupBy('parent_id');
        $result = [];

        $walk = function ($parentId, $depth) use (&$walk, &$result, $all, $excludeId) {
            foreach ($all->get($parentId, collect()) as $category) {
                if ($category->id === $excludeId) {
                    continue;
                }

                $result[] = ['id' => $category->id, 'name' => str_repeat('— ', $depth).$category->name_ar, 'depth' => $depth];
                $walk($category->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $result;
    }
}
