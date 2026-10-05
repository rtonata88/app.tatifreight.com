<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'inspector_id',
        'inspection_date',
        'inspection_type',
        'mileage',
        'checklist',
        'passed',
        'issues_found',
        'recommendations',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'mileage' => 'decimal:2',
            'checklist' => 'array',
            'passed' => 'boolean',
            'photos' => 'array',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
