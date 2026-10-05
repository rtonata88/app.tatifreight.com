<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
        'branch_code',
        'swift_code',
        'currency',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the primary bank account
     */
    public static function primary()
    {
        return self::where('is_primary', true)->where('is_active', true)->first();
    }

    /**
     * Get all active bank accounts
     */
    public static function active()
    {
        return self::where('is_active', true)->orderBy('is_primary', 'desc')->get();
    }
}
