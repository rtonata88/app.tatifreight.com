<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MdcRateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'min_gvm_tonnes',
        'max_gvm_tonnes',
        'rate_per_100km',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'min_gvm_tonnes' => 'decimal:2',
            'max_gvm_tonnes' => 'decimal:2',
            'rate_per_100km' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Vehicles that are linked to this rate card
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * MDC calculations that used this rate card
     */
    public function mdcCalculations(): HasMany
    {
        return $this->hasMany(MdcCalculation::class);
    }

    /**
     * Get active rate card for a given GVM on a specific date
     */
    public static function getActiveRateForGvm(float $gvmTonnes, $date = null)
    {
        $date = $date ?? now();

        return self::where('is_active', true)
            ->where('min_gvm_tonnes', '<=', $gvmTonnes)
            ->where(function ($query) use ($gvmTonnes) {
                $query->whereNull('max_gvm_tonnes')
                      ->orWhere('max_gvm_tonnes', '>=', $gvmTonnes);
            })
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}

