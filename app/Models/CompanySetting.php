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
    ];

    /**
     * Get the company settings (singleton pattern)
     */
    public static function get()
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'company_name' => 'TAATI Transport',
                'country' => 'Namibia',
            ]);
        }

        return $settings;
    }
}
