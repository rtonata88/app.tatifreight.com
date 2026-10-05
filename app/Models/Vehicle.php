<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_type_id',
        'reg_number',
        'vin',
        'make',
        'model',
        'year',
        'load_capacity',
        'tare_weight',
        'gvm_tonnes',
        'mdc_rate_card_id',
        'insurance_expiry',
        'disc_expiry',
        'roadworthy_expiry',
        'status',
        'current_mileage',
        'gps_device_id',
        'license_disc_path',
        'insurance_path',
        'photos',
        'next_service_date',
        'next_service_mileage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'load_capacity' => 'decimal:2',
            'tare_weight' => 'decimal:2',
            'gvm_tonnes' => 'decimal:2',
            'current_mileage' => 'decimal:2',
            'insurance_expiry' => 'date',
            'disc_expiry' => 'date',
            'roadworthy_expiry' => 'date',
            'next_service_date' => 'date',
            'photos' => 'array',
        ];
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function mdcRateCard(): BelongsTo
    {
        return $this->belongsTo(MdcRateCard::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function mdcCalculations(): HasMany
    {
        return $this->hasMany(MdcCalculation::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }

    public function quoteLineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class);
    }

    public function invoiceLineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * Get suggested MDC rate card based on vehicle's GVM
     * 
     * @return MdcRateCard|null
     */
    public function suggestedMdcRateCard()
    {
        if (!$this->gvm_tonnes) {
            return null;
        }

        return MdcRateCard::getActiveRateForGvm($this->gvm_tonnes);
    }

    /**
     * Get the effective MDC rate card (linked or suggested)
     * 
     * @return MdcRateCard|null
     */
    public function getEffectiveMdcRateCard()
    {
        // Return the manually linked rate card if it exists
        if ($this->mdc_rate_card_id && $this->mdcRateCard) {
            return $this->mdcRateCard;
        }

        // Otherwise, return the suggested rate based on GVM
        return $this->suggestedMdcRateCard();
    }

    /**
     * Check if vehicle is available for a given date range
     * 
     * @param \DateTime|string $startDate
     * @param \DateTime|string $endDate
     * @param int|null $excludeBookingId Booking ID to exclude from check (for editing)
     * @return bool
     */
    public function isAvailable($startDate, $endDate, $excludeBookingId = null)
    {
        $query = $this->bookings()
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where(function($query) use ($startDate, $endDate) {
                // Check for any overlap between existing bookings and requested dates
                $query->where(function($q) use ($startDate, $endDate) {
                    // Existing booking starts during requested period
                    $q->whereBetween('start_date', [$startDate, $endDate]);
                })
                ->orWhere(function($q) use ($startDate, $endDate) {
                    // Existing booking ends during requested period
                    $q->whereBetween('end_date', [$startDate, $endDate]);
                })
                ->orWhere(function($q) use ($startDate, $endDate) {
                    // Existing booking completely encompasses requested period
                    $q->where('start_date', '<=', $startDate)
                      ->where('end_date', '>=', $endDate);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return !$query->exists();
    }
}
