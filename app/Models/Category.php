<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name_ar', 'name_en', 'slug', 'description', 'image', 'sort_order',
        'is_active', 'seo_title', 'seo_description', 'seo_keywords',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('categories.rows');
        static::saved($flush);
        static::deleted($flush);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute(): string
    {
        return $this->name_ar;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getUrlAttribute(): string
    {
        return route('categories.show', $this->slug);
    }

    /** This category id plus every nested descendant id. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /** True when $candidateId is this category or one of its descendants. */
    public function hasInTree(int $candidateId): bool
    {
        return in_array($candidateId, $this->descendantIds(), true);
    }
}
