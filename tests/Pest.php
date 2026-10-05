<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A user holding the given role, after seeding the real roles and permissions.
 * Admins also get every permission, as in production.
 */
function userWithRole(string $role): App\Models\User
{
    (new Database\Seeders\RolesAndPermissionsSeeder)->run();

    $user = App\Models\User::factory()->create();
    $user->assignRole($role);

    if ($role === 'admin') {
        Spatie\Permission\Models\Role::findByName('admin')->givePermissionTo(Spatie\Permission\Models\Permission::all());
    }

    return $user->fresh();
}

/**
 * A user holding exactly the given permissions (created if missing).
 *
 * @param  list<string>  $permissions
 */
function userWithPermissions(array $permissions): App\Models\User
{
    foreach ($permissions as $permission) {
        Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
    }

    $user = App\Models\User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user->fresh();
}
