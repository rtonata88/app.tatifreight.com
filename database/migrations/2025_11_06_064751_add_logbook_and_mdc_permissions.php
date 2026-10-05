<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create new permissions
        $newPermissions = [
            'view-logbook',
            'create-logbook',
            'edit-logbook',
            'delete-logbook',
            'view-mdc',
            'manage-mdc',
            'manage-mdc-rates',
        ];

        foreach ($newPermissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName]);
            }
        }

        // Update Driver role - remove vehicle permissions, add logbook permissions
        $driver = Role::findByName('driver');
        if ($driver) {
            $driver->revokePermissionTo(['view-vehicles', 'create-vehicles', 'edit-vehicles']);
            $driver->givePermissionTo(['view-logbook', 'create-logbook', 'edit-logbook']);
        }

        // Update Manager role - add new permissions
        $manager = Role::findByName('manager');
        if ($manager) {
            $manager->givePermissionTo([
                'view-logbook', 'create-logbook', 'edit-logbook', 'delete-logbook',
                'view-mdc', 'manage-mdc', 'manage-mdc-rates',
            ]);
        }

        // Update Admin role - give all permissions
        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }

        // Update Dispatcher role - add logbook view permission
        $dispatcher = Role::findByName('dispatcher');
        if ($dispatcher) {
            $dispatcher->givePermissionTo('view-logbook');
        }

        // Update Accountant role - add MDC permissions
        $accountant = Role::findByName('accountant');
        if ($accountant) {
            $accountant->givePermissionTo(['view-mdc', 'manage-mdc', 'manage-mdc-rates']);
        }

        // Reset cached roles and permissions again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Restore Driver role permissions
        $driver = Role::findByName('driver');
        if ($driver) {
            $driver->revokePermissionTo(['view-logbook', 'create-logbook', 'edit-logbook']);
            $driver->givePermissionTo(['view-vehicles', 'create-vehicles', 'edit-vehicles']);
        }

        // Remove new permissions from all roles
        $newPermissions = [
            'view-logbook',
            'create-logbook',
            'edit-logbook',
            'delete-logbook',
            'view-mdc',
            'manage-mdc',
            'manage-mdc-rates',
        ];

        foreach ($newPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permission->delete();
            }
        }

        // Reset cached roles and permissions again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
