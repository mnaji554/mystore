<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = ['product_id', 'user_id', 'rating', 'title', 'comment', 'status', 'approved_at'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'rating' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::APPROVED);
    }
}
