<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'label', 'full_name', 'phone', 'country', 'city', 'district',
        'street', 'building', 'postal_code', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Snapshot stored on orders. */
    public function toSnapshot(): array
    {
        return $this->only([
            'full_name', 'phone', 'country', 'city', 'district', 'street', 'building', 'postal_code',
        ]);
    }

    public function getSummaryAttribute(): string
    {
        return collect([$this->street, $this->building, $this->district, $this->city, $this->country])
            ->filter()->implode('، ');
    }
}
