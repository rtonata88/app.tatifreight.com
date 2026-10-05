<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MdcPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'payment_date',
        'amount',
        'payment_method',
        'bank_reference',
        'receipt_path',
        'notes',
        'expense_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * The expense record auto-created for this payment
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * The user who recorded this payment
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * MDC calculations covered by this payment
     */
    public function mdcCalculations(): BelongsToMany
    {
        return $this->belongsToMany(MdcCalculation::class, 'mdc_calculation_payment')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    /**
     * Generate next payment reference
     */
    public static function generatePaymentReference(): string
    {
        $lastPayment = self::latest('id')->first();
        $nextNumber = $lastPayment ? (int)substr($lastPayment->payment_reference, 4) + 1 : 1;
        return 'MDC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
