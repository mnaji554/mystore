<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'status' => $this->status,
            'author' => $this->whenLoaded('user', fn () => $this->user?->name),
            'images' => $this->whenLoaded('images', fn () => $this->images->map->url->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
