<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = ['payment_id', 'type', 'status', 'amount', 'provider_reference', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'amount' => 'decimal:2'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
