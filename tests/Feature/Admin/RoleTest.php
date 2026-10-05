<?php

require_once __DIR__.'/helpers.php';

use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

test('guests cannot see roles', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
});

test('non-admins are forbidden from roles', function () {
    $this->actingAs(adminTestUser('manager'))->get(route('roles.index'))->assertForbidden();
});

test('roles page shows role counts and permissions grouped by module', function () {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertInertia(function (Assert $page) {
            $page->component('roles/index')
                ->has('roles', 5)
                ->has('modules');

            $props = $page->toArray()['props'];
            $admin = collect($props['roles'])->firstWhere('name', 'admin');
            $driver = collect($props['roles'])->firstWhere('name', 'driver');
            $vehicles = collect($props['modules'])->firstWhere('module', 'vehicles');

            expect($admin['users_count'])->toBe(1)
                ->and($admin['permissions_count'])->toBe(Permission::count())
                ->and($driver['permissions'])->not->toContain('view-vehicles')
                ->and($vehicles['permissions'])->toContain('view-vehicles', 'create-vehicles', 'edit-vehicles', 'delete-vehicles');
        });
});
