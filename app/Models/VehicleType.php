<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_rate_hourly',
        'base_rate_daily',
        'base_rate_per_km',
        'requires_mdc',
    ];

    protected function casts(): array
    {
        return [
            'requires_mdc' => 'boolean',
            'base_rate_hourly' => 'decimal:2',
            'base_rate_daily' => 'decimal:2',
            'base_rate_per_km' => 'decimal:2',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(RateCard::class);
    }
}
