<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Logbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'booking_id',
        'date',
        'start_odometer',
        'end_odometer',
        'origin_from',
        'origin_to',
        'purpose',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_odometer' => 'decimal:2',
            'end_odometer' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mdcCalculation(): HasOne
    {
        return $this->hasOne(MdcCalculation::class);
    }

    public function mdcCalculations(): HasMany
    {
        return $this->hasMany(MdcCalculation::class);
    }

    public function getDistanceTravelledAttribute(): float
    {
        if ($this->end_odometer && $this->start_odometer) {
            return $this->end_odometer - $this->start_odometer;
        }
        return 0;
    }

    /**
     * Boot the model and register event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($logbook) {
            $logbook->createOrUpdateMdcCalculation();
            $logbook->updateVehicleMileage();
        });

        static::updated(function ($logbook) {
            // Only recalculate if odometer or vehicle changed
            if ($logbook->wasChanged(['end_odometer', 'start_odometer', 'vehicle_id', 'date'])) {
                $logbook->createOrUpdateMdcCalculation();
            }
            
            // Update vehicle mileage if end_odometer changed
            if ($logbook->wasChanged('end_odometer')) {
                $logbook->updateVehicleMileage();
            }
        });
    }

    /**
     * Create or update MDC calculation for this logbook entry
     * 
     * @return MdcCalculation|null
     */
    public function createOrUpdateMdcCalculation(): ?MdcCalculation
    {
        try {
            // Check if end_odometer exists (required for distance calculation)
            if (!$this->end_odometer || !$this->start_odometer) {
                return null;
            }

            // Load vehicle with GVM (refresh relationship if needed)
            $vehicle = $this->vehicle()->first();
            if (!$vehicle) {
                Log::warning("Logbook {$this->id}: Vehicle not found for MDC calculation");
                return null;
            }

            // Check if vehicle has GVM
            if (!$vehicle->gvm_tonnes) {
                Log::warning("Logbook {$this->id}: Vehicle {$vehicle->reg_number} has no GVM set. MDC calculation skipped.");
                return null;
            }

            // Calculate distance
            $distanceKm = $this->distance_travelled;
            if ($distanceKm <= 0) {
                Log::warning("Logbook {$this->id}: Invalid distance calculated ({$distanceKm} km). MDC calculation skipped.");
                return null;
            }

            // Get active MDC rate card
            $rateCard = $vehicle->getEffectiveMdcRateCard();
            if (!$rateCard) {
                Log::warning("Logbook {$this->id}: No active MDC rate card found for vehicle {$vehicle->reg_number} (GVM: {$vehicle->gvm_tonnes} tonnes). MDC calculation skipped.");
                return null;
            }

            // Calculate MDC amount
            // Ensure date is in correct format (Carbon instance or date string)
            $calculationDate = $this->date instanceof \Carbon\Carbon 
                ? $this->date 
                : \Carbon\Carbon::parse($this->date);

            $calculation = MdcCalculation::calculateWithRateCard(
                $vehicle->gvm_tonnes,
                $distanceKm,
                $calculationDate
            );

            // Check if MDC already exists
            $existingMdc = $this->mdcCalculation;

            if ($existingMdc) {
                // Handle vehicle change - delete old MDC if unpaid and vehicle changed
                $vehicleChanged = $existingMdc->vehicle_id !== $vehicle->id;
                if ($vehicleChanged && $existingMdc->payment_status === 'unpaid') {
                    $existingMdc->delete();
                    $existingMdc = null;
                }

                // Update existing MDC
                if ($existingMdc) {
                    $oldDistance = $existingMdc->distance_km;
                    $oldAmount = $existingMdc->mdc_amount;
                    
                    // Check for significant distance decrease (potential error)
                    if ($distanceKm < ($oldDistance * 0.5)) {
                        Log::warning("Logbook {$this->id}: Distance decreased significantly from {$oldDistance} km to {$distanceKm} km. MDC updated but may need review.");
                    }

                    // Update MDC calculation
                    // Note: rate_per_100kg_km is legacy field, we use mdc_rate_card_id for new calculations
                    $existingMdc->update([
                        'distance_km' => $distanceKm,
                        'gvm_tonnes' => $vehicle->gvm_tonnes,
                        'mdc_rate_card_id' => $calculation['rate_card_id'],
                        'mdc_amount' => $calculation['mdc_amount'],
                        'calculation_date' => $calculationDate->format('Y-m-d'),
                    ]);

                    // If unpaid, recalculate payment status based on new amount
                    if ($existingMdc->payment_status === 'unpaid') {
                        // Amount might have changed, but status stays unpaid
                        $existingMdc->refresh();
                    } else {
                        // For paid/partially_paid, preserve payment status and amount_paid
                        // The outstanding amount will be recalculated automatically via accessor
                    }

                    return $existingMdc;
                }
            }

            // Create new MDC calculation
            // Note: rate_per_100kg_km is legacy field, we use mdc_rate_card_id for new calculations
            $mdcCalculation = MdcCalculation::create([
                'logbook_id' => $this->id,
                'vehicle_id' => $vehicle->id,
                'mdc_rate_card_id' => $calculation['rate_card_id'],
                'gvm_tonnes' => $vehicle->gvm_tonnes,
                'distance_km' => $distanceKm,
                'mdc_amount' => $calculation['mdc_amount'],
                'calculation_date' => $calculationDate->format('Y-m-d'),
                'payment_status' => 'unpaid',
                'amount_paid' => 0.00,
            ]);

            return $mdcCalculation;

        } catch (\Exception $e) {
            // Log error but don't fail logbook save
            Log::error("Logbook {$this->id}: Failed to create/update MDC calculation", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update vehicle's current mileage based on this logbook entry
     * Only updates if end_odometer is higher than current mileage
     */
    public function updateVehicleMileage(): void
    {
        try {
            // Only update if end_odometer exists
            if (!$this->end_odometer) {
                return;
            }

            $vehicle = $this->vehicle()->first();
            if (!$vehicle) {
                return;
            }

            // Update vehicle mileage if this logbook entry's end_odometer is higher
            // This ensures we always have the highest recorded odometer reading
            if ($this->end_odometer > $vehicle->current_mileage) {
                $vehicle->update([
                    'current_mileage' => $this->end_odometer
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail logbook save
            Log::error("Logbook {$this->id}: Failed to update vehicle mileage", [
                'error' => $e->getMessage(),
                'vehicle_id' => $this->vehicle_id,
                'end_odometer' => $this->end_odometer
            ]);
        }
    }
}
