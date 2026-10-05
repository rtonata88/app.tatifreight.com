<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Replaces the Volt component livewire/roles/index (a read-only overview).
 */
class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users', 'permissions')->with('permissions')->get();

        // Group permissions by module (e.g. view-vehicles -> vehicles), as before.
        $modules = Permission::all()
            ->groupBy(function (Permission $permission) {
                $parts = explode('-', $permission->name);

                return count($parts) > 1 ? $parts[1] : 'other';
            })
            ->map(fn ($permissions, $module) => [
                'module' => (string) $module,
                'permissions' => $permissions->pluck('name')->values(),
            ])
            ->values();

        return Inertia::render('roles/index', [
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]),
            'modules' => $modules,
        ]);
    }
}
