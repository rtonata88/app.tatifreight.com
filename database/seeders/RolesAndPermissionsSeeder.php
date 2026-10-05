<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Vehicle permissions
            'view-vehicles',
            'create-vehicles',
            'edit-vehicles',
            'delete-vehicles',
            'manage-vehicle-inspections',

            // Logbook permissions
            'view-logbook',
            'create-logbook',
            'edit-logbook',
            'delete-logbook',

            // MDC permissions
            'view-mdc',
            'manage-mdc',
            'manage-mdc-rates',

            // Client permissions
            'view-clients',
            'create-clients',
            'edit-clients',
            'delete-clients',

            // Booking permissions
            'view-bookings',
            'create-bookings',
            'edit-bookings',
            'delete-bookings',
            'assign-drivers',
            'confirm-bookings',

            // Quote permissions
            'view-quotes',
            'create-quotes',
            'edit-quotes',
            'delete-quotes',
            'send-quotes',
            'approve-quotes',

            // Invoice permissions
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'send-invoices',
            'record-payments',

            // Expense permissions
            'view-expenses',
            'create-expenses',
            'edit-expenses',
            'delete-expenses',
            'approve-expenses',

            // Rate card permissions
            'view-rate-cards',
            'create-rate-cards',
            'edit-rate-cards',
            'delete-rate-cards',

            // Reports permissions
            'view-reports',
            'export-reports',

            // System settings
            'manage-settings',
            'manage-users',
            'manage-roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin - Full access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager - All except system settings
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view-vehicles', 'create-vehicles', 'edit-vehicles', 'delete-vehicles', 'manage-vehicle-inspections',
            'view-logbook', 'create-logbook', 'edit-logbook', 'delete-logbook',
            'view-mdc', 'manage-mdc', 'manage-mdc-rates',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-bookings', 'create-bookings', 'edit-bookings', 'delete-bookings', 'assign-drivers', 'confirm-bookings',
            'view-quotes', 'create-quotes', 'edit-quotes', 'delete-quotes', 'send-quotes', 'approve-quotes',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'send-invoices', 'record-payments',
            'view-expenses', 'create-expenses', 'edit-expenses', 'delete-expenses', 'approve-expenses',
            'view-rate-cards', 'create-rate-cards', 'edit-rate-cards', 'delete-rate-cards',
            'view-reports', 'export-reports',
        ]);

        // Dispatcher - Bookings, Quotes, Clients
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher']);
        $dispatcher->givePermissionTo([
            'view-vehicles',
            'view-logbook',
            'view-clients', 'create-clients', 'edit-clients',
            'view-bookings', 'create-bookings', 'edit-bookings', 'assign-drivers', 'confirm-bookings',
            'view-quotes', 'create-quotes', 'edit-quotes', 'send-quotes',
            'view-invoices',
        ]);

        // Driver - View assigned jobs, submit expenses, manage logbook
        $driver = Role::firstOrCreate(['name' => 'driver']);
        $driver->givePermissionTo([
            'view-bookings',
            'view-expenses', 'create-expenses',
            'view-logbook', 'create-logbook', 'edit-logbook', // Logbook only, no MDC access
        ]);

        // Accountant - Financials + Reports
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->givePermissionTo([
            'view-clients',
            'view-bookings',
            'view-quotes',
            'view-invoices', 'create-invoices', 'edit-invoices', 'send-invoices', 'record-payments',
            'view-expenses', 'approve-expenses',
            'view-rate-cards', 'create-rate-cards', 'edit-rate-cards',
            'view-mdc', 'manage-mdc', 'manage-mdc-rates',
            'view-reports', 'export-reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
