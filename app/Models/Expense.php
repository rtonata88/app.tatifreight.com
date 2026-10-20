<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'booking_id',
        'user_id',
        'driver_id',
        'category',
        'amount',
        'litres',
        'price_per_litre',
        'odometer',
        'full_tank',
        'station',
        'consumption_l_per_100km',
        'expense_date',
        'description',
        'receipt_path',
        'odometer_photo_path',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'litres' => 'decimal:2',
            'price_per_litre' => 'decimal:3',
            'odometer' => 'decimal:2',
            'full_tank' => 'boolean',
            'consumption_l_per_100km' => 'decimal:2',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The driver who filled up (fuel expenses); the submitter is `user`. */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function fuelAlerts(): HasMany
    {
        return $this->hasMany(FuelAlert::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
