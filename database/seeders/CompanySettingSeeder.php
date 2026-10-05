<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanySetting::create([
            'company_name' => 'TAATI Transport',
            'email' => 'info@taati.com.na',
            'phone' => '+264 61 123 4567',
            'address' => 'Industrial Area',
            'city' => 'Windhoek',
            'country' => 'Namibia',
            'quote_footer' => 'Thank you for your business!',
            'invoice_footer' => 'Payment terms: Net 30 days. Late payments subject to interest charges.',
        ]);
    }
}
