<?php

namespace Database\Seeders;

use App\Models\MdcRateCard;
use Illuminate\Database\Seeder;

class MdcRateCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Official RFANAM (Road Fund Administration Namibia) Mass Distance Charges
     * Source: https://www.rfanam.com.na/charges/
     * 
     * MDC rates are based on Gross Vehicle Mass (GVM) in kilograms
     * Rates shown are N$ per 100km
     */
    public function run(): void
    {
        // Clear existing rates first (disable foreign key checks temporarily)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MdcRateCard::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $mdcRates = [
            // Charge Level 1: More than 3,500 kg and ≤ 7,000 kg
            [
                'category_name' => 'Charge Level 1 - Bus (3.5t - 7t)',
                'min_gvm_tonnes' => 3.501,
                'max_gvm_tonnes' => 7.0,
                'rate_per_100km' => 10.10,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Minibus, designed for 17 to 35 persons, including the driver',
            ],
            [
                'category_name' => 'Charge Level 1 - Goods Vehicle (3.5t - 7t)',
                'min_gvm_tonnes' => 3.501,
                'max_gvm_tonnes' => 7.0,
                'rate_per_100km' => 10.10,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck - More than 3,500 kg and less than or equal to 7,000 kg',
            ],

            // Charge Level 2: More than 7,000 kg and ≤ 16,000 kg
            [
                'category_name' => 'Charge Level 2 - Bus (7t - 16t)',
                'min_gvm_tonnes' => 7.001,
                'max_gvm_tonnes' => 16.0,
                'rate_per_100km' => 12.22,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Minibus, designed for 17 to 35 persons, including the driver',
            ],
            [
                'category_name' => 'Charge Level 2 - Goods Vehicle (7t - 16t)',
                'min_gvm_tonnes' => 7.001,
                'max_gvm_tonnes' => 16.0,
                'rate_per_100km' => 12.22,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck - More than 7,000 kg and less than or equal to 16,000 kg',
            ],

            // Charge Level 3: More than 16,000 kg and ≤ 34,000 kg
            [
                'category_name' => 'Charge Level 3 - Bus (16t - 34t)',
                'min_gvm_tonnes' => 16.001,
                'max_gvm_tonnes' => 34.0,
                'rate_per_100km' => 22.16,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Bus or bus-train designed for 35 persons, including the driver',
            ],
            [
                'category_name' => 'Charge Level 3 - Goods Vehicle (16t - 34t)',
                'min_gvm_tonnes' => 16.001,
                'max_gvm_tonnes' => 34.0,
                'rate_per_100km' => 22.16,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck - More than 16,000 kg and less than or equal to 34,000 kg',
            ],
            [
                'category_name' => 'Charge Level 3 - Truck-tractor (16t - 34t)',
                'min_gvm_tonnes' => 16.001,
                'max_gvm_tonnes' => 34.0,
                'rate_per_100km' => 22.16,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck-tractor - More than 16,000 kg and less than or equal to 34,000 kg',
            ],

            // Charge Level 4: More than 34,000 kg and ≤ 44,000 kg
            [
                'category_name' => 'Charge Level 4 - Goods Vehicle (34t - 44t)',
                'min_gvm_tonnes' => 34.001,
                'max_gvm_tonnes' => 44.0,
                'rate_per_100km' => 44.47,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck - More than 34,000 kg and less than or equal to 44,000 kg',
            ],
            [
                'category_name' => 'Charge Level 4 - Truck-tractor (34t - 44t)',
                'min_gvm_tonnes' => 34.001,
                'max_gvm_tonnes' => 44.0,
                'rate_per_100km' => 44.47,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck-tractor - More than 34,000 kg and less than or equal to 44,000 kg',
            ],

            // Charge Level 5: More than 44,000 kg
            [
                'category_name' => 'Charge Level 5 - Truck-tractor (44t+)',
                'min_gvm_tonnes' => 44.001,
                'max_gvm_tonnes' => null,
                'rate_per_100km' => 66.64,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Truck-tractor - More than 44,000 kg',
            ],

            // Light vehicles (under 3.5t) - Exempt from MDC
            [
                'category_name' => 'Light Vehicle (0 - 3.5t) - Exempt',
                'min_gvm_tonnes' => 0,
                'max_gvm_tonnes' => 3.5,
                'rate_per_100km' => 0.00,
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'Vehicles under 3,500 kg are exempt from MDC charges',
            ],
        ];

        foreach ($mdcRates as $rate) {
            MdcRateCard::create($rate);
        }

        $this->command->info('✓ MDC Rate Cards seeded successfully with ' . count($mdcRates) . ' official RFANAM rate categories!');
        $this->command->info('✓ Rates loaded: Level 1 (N$10.10), Level 2 (N$12.22), Level 3 (N$22.16), Level 4 (N$44.47), Level 5 (N$66.64)');
    }
}
