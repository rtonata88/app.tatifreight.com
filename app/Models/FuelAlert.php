<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A fill-up that failed one of the fuel checks (App\Services\FuelChecks). A manager marks it
 * reviewed with a note, so the owner can see what was followed up.
 */
class FuelAlert extends Model
{
    /** Alert types and the heading each shows under. */
    public const TYPES = [
        'high_consumption' => 'High consumption',
        'over_tank_capacity' => 'Over tank capacity',
        'no_trips' => 'Fuel without trips',
        'fills_too_close' => 'Fills too close',
        'odometer_mismatch' => 'Odometer mismatch',
        'price_out_of_range' => 'Price out of range',
        'missing_receipt' => 'Missing receipt',
    ];

    protected $fillable = [
        'expense_id',
        'vehicle_id',
        'driver_id',
        'type',
        'message',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @param  Builder<self>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('reviewed_at');
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
