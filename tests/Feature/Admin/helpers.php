<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * The shared userWithRole() helper calls RolesAndPermissionsSeeder::run() directly, and the seeder's
 * final $this->command->info() fails when no console command is attached. This version seeds
 * through $this->seed() (which attaches one) and otherwise does the same thing.
 */
if (! function_exists('adminTestUser')) {
    function adminTestUser(string $role = 'admin'): User
    {
        if (! Role::where('name', $role)->exists()) {
            test()->seed(RolesAndPermissionsSeeder::class);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        if ($role === 'admin') {
            Role::findByName('admin')->givePermissionTo(Permission::all());
        }

        return $user->fresh();
    }
}
