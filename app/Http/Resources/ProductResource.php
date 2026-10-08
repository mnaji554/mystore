<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->when($this->relationLoaded('images'), $this->description),
            'price' => (float) $this->from_regular_price,
            'final_price' => $this->from_price,
            'discount_percent' => $this->discount_percent,
            'in_stock' => $this->in_stock,
            'stock' => $this->available_stock,
            'is_new' => $this->is_new,
            'is_bestseller' => $this->is_bestseller,
            'is_featured' => $this->is_featured,
            'rating' => (float) $this->rating_avg,
            'reviews_count' => $this->reviews_count,
            'image' => $this->image_url,
            'thumbnail' => $this->thumb_url,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => ['url' => $i->url, 'thumbnail' => $i->thumb_url, 'primary' => $i->is_primary])->values()),
            'category' => $this->whenLoaded('category', fn () => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name_ar, 'slug' => $this->category->slug] : null),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->where('is_active', true)->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => (float) $v->price,
                'final_price' => $v->final_price,
                'stock' => $v->stock,
                'options' => $v->relationLoaded('options') ? $v->options->mapWithKeys(fn ($o) => [$o->name => $o->value])->all() : [],
            ])->values()),
            'url' => $this->url,
        ];
    }
}
