<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_type_id',
        'client_id',
        'name',
        'rate_type',
        'rate',
        'includes_mdc',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'includes_mdc' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
