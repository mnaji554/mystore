<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierProfile extends Model
{
    protected $hidden = [
        'national_id',
        'vehicle_plate',
        'driving_license_number',
        'national_id_image_path',
        'vehicle_registration_image_path',
        'driving_license_image_path',
    ];

    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'vehicle_plate' => 'encrypted',
            'driving_license_number' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
