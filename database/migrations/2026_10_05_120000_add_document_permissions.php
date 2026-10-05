<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The Documents module checks view/create/edit/delete-documents, but those permissions were
 * never created, so every user (admins included) was refused. Create them and grant them the
 * way RolesAndPermissionsSeeder does for new installs.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'view-documents',
        'create-documents',
        'edit-documents',
        'delete-documents',
    ];

    private const GRANTS = [
        'admin' => self::PERMISSIONS,
        'manager' => self::PERMISSIONS,
        'dispatcher' => ['view-documents', 'create-documents', 'edit-documents'],
        'accountant' => ['view-documents'],
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::GRANTS as $roleName => $permissions) {
            Role::where('name', $roleName)->first()?->givePermissionTo($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', self::PERMISSIONS)->get()->each->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
