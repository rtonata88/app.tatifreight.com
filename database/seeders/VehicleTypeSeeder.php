<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            [
                'name' => 'Tipper Truck',
                'description' => 'Construction and mining tipper trucks',
                'base_rate_hourly' => 450.00,
                'base_rate_daily' => 3500.00,
                'base_rate_per_km' => 25.00,
                'requires_mdc' => true,
            ],
            [
                'name' => '14-Ton Cooler Truck',
                'description' => 'Refrigerated transport for perishables',
                'base_rate_hourly' => 550.00,
                'base_rate_daily' => 4200.00,
                'base_rate_per_km' => 30.00,
                'requires_mdc' => true,
            ],
            [
                'name' => '34-Ton Superlink',
                'description' => 'Heavy freight long-haul transport',
                'base_rate_hourly' => 650.00,
                'base_rate_daily' => 5000.00,
                'base_rate_per_km' => 35.00,
                'requires_mdc' => true,
            ],
            [
                'name' => 'Support Vehicle',
                'description' => 'Light support and logistics vehicles',
                'base_rate_hourly' => 250.00,
                'base_rate_daily' => 1800.00,
                'base_rate_per_km' => 15.00,
                'requires_mdc' => false,
            ],
        ];

        foreach ($vehicleTypes as $type) {
            VehicleType::create($type);
        }

        $this->command->info('Vehicle types seeded successfully!');
    }
}
