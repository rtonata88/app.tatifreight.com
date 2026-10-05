<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MdcCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'logbook_id',
        'vehicle_id',
        'mdc_rate_card_id',
        'gvm_tonnes',
        'distance_km',
        'tare_weight',
        'load_weight',
        'total_mass',
        'rate_per_100kg_km',
        'mdc_amount',
        'calculation_date',
        'payment_status',
        'amount_paid',
    ];

    protected function casts(): array
    {
        return [
            'gvm_tonnes' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'tare_weight' => 'decimal:2',
            'load_weight' => 'decimal:2',
            'total_mass' => 'decimal:2',
            'rate_per_100kg_km' => 'decimal:4',
            'mdc_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'calculation_date' => 'date',
        ];
    }

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mdcRateCard(): BelongsTo
    {
        return $this->belongsTo(MdcRateCard::class);
    }

    /**
     * Payments applied to this MDC calculation
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(MdcPayment::class, 'mdc_calculation_payment')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    /**
     * Get the outstanding amount for this calculation
     */
    public function getOutstandingAmountAttribute(): float
    {
        return (float)($this->mdc_amount - $this->amount_paid);
    }

    /**
     * Check if this calculation is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if this calculation is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    /**
     * Calculate MDC amount using RFANAM rate cards based on GVM
     * 
     * @param float $gvmTonnes Gross Vehicle Mass in tonnes
     * @param float $distanceKm Distance traveled in kilometers
     * @param \DateTime|null $date Date for rate lookup (defaults to today)
     * @return array ['rate_per_100km' => float, 'mdc_amount' => float, 'category_name' => string]
     */
    public static function calculateWithRateCard(float $gvmTonnes, float $distanceKm, $date = null): array
    {
        $rateCard = MdcRateCard::getActiveRateForGvm($gvmTonnes, $date);
        
        if (!$rateCard) {
            throw new \Exception("No active MDC rate found for GVM: {$gvmTonnes} tonnes");
        }
        
        // Calculate MDC: (distance_km / 100) × rate_per_100km
        $mdcAmount = ($distanceKm / 100) * $rateCard->rate_per_100km;
        
        return [
            'rate_per_100km' => $rateCard->rate_per_100km,
            'mdc_amount' => round($mdcAmount, 2),
            'category_name' => $rateCard->category_name,
            'rate_card_id' => $rateCard->id,
        ];
    }

    /**
     * Calculate MDC amount (legacy method for backward compatibility)
     * Formula: (tare + load) × distance × (rate per 100kg per km)
     */
    public static function calculate(float $tareWeight, float $loadWeight, float $distanceKm, float $ratePerKm): float
    {
        $totalMass = $tareWeight + $loadWeight;
        return ($totalMass * $distanceKm * $ratePerKm) / 100;
    }
}
