<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolesAndPermissionsSeeder::class,
            VehicleTypeSeeder::class,
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@taati.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@taati.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // Create dispatcher user
        $dispatcher = User::create([
            'name' => 'Dispatcher User',
            'email' => 'dispatcher@taati.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $dispatcher->assignRole('dispatcher');

        // Create driver user
        $driver = User::create([
            'name' => 'Driver User',
            'email' => 'driver@taati.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $driver->assignRole('driver');

        // Create accountant user
        $accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant@taati.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $accountant->assignRole('accountant');

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@taati.com / password');
        $this->command->info('Manager: manager@taati.com / password');
        $this->command->info('Dispatcher: dispatcher@taati.com / password');
        $this->command->info('Driver: driver@taati.com / password');
        $this->command->info('Accountant: accountant@taati.com / password');
    }
}
