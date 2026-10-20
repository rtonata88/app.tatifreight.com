<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'vat_number',
        'registration_number',
        'email',
        'phone',
        'secondary_phone',
        'address',
        'city',
        'postal_code',
        'country',
        'website',
        'logo_path',
        'signature_path',
        'invoice_footer',
        'quote_footer',
        'bank_name',
        'bank_branch',
        'account_name',
        'account_number',
        'branch_code',
        'swift_code',
        'mdc_payment_due_day',
        'fuel_consumption_tolerance',
        'fuel_min_km_between_fills',
        'fuel_price_tolerance',
        'fuel_trip_window_days',
        'fuel_price_per_litre',
    ];

    /** Same as the column defaults, so a settings row created in code has them before it is reloaded. */
    protected $attributes = [
        'fuel_consumption_tolerance' => 15,
        'fuel_min_km_between_fills' => 150,
        'fuel_price_tolerance' => 10,
        'fuel_trip_window_days' => 2,
    ];

    protected function casts(): array
    {
        return [
            'mdc_payment_due_day' => 'integer',
            'fuel_consumption_tolerance' => 'integer',
            'fuel_min_km_between_fills' => 'integer',
            'fuel_price_tolerance' => 'integer',
            'fuel_trip_window_days' => 'integer',
            'fuel_price_per_litre' => 'decimal:3',
        ];
    }

    /**
     * Get the company settings (singleton pattern)
     */
    public static function get()
    {
        $settings = self::first();

        if (! $settings) {
            $settings = self::create([
                'company_name' => 'TAATI Transport',
                'country' => 'Namibia',
            ]);
        }

        return $settings;
    }
}
