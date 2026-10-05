<?php

require_once __DIR__.'/helpers.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see users', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('non-admins are forbidden from user management', function () {
    $manager = adminTestUser('manager');

    $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
    $this->actingAs($manager)->get(route('users.create'))->assertForbidden();
    $this->actingAs($manager)->post(route('users.store'), [])->assertForbidden();
});

test('users index lists, searches and filters by role', function () {
    $admin = adminTestUser('admin');
    $driver = User::factory()->create(['name' => 'Penda Driver', 'email' => 'penda@example.com']);
    $driver->assignRole('driver');
    User::factory()->create(['name' => 'Someone Else'])->assignRole('manager');

    $this->actingAs($admin)
        ->get(route('users.index', ['role' => 'driver']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Penda Driver')
            ->where('users.data.0.roles', ['driver'])
            ->where('users.data.0.is_self', false)
            ->where('filters.role', 'driver')
        );

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'penda@']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.email', 'penda@example.com'));
});

test('an admin can create a user with a role', function () {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page->component('users/create')->has('roles', 5));

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'dispatcher',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'User created successfully!');

    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect($user->hasRole('dispatcher'))->toBeTrue()
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

test('creating a user validates password rules, unique email and role', function () {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => '',
            'email' => $admin->email,
            'password' => 'short',
            'password_confirmation' => 'different',
            'role' => 'not-a-role',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password', 'password_confirmation', 'role']);
});

test('an admin can update a user, keeping the password when left blank', function () {
    $admin = adminTestUser('admin');
    $user = User::factory()->create();
    $user->assignRole('driver');
    $oldHash = $user->password;

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertInertia(fn (Assert $page) => $page->component('users/edit')->where('user.id', $user->id)->where('user.role', 'driver'));

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'manager',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'User updated successfully!');

    $user->refresh();
    expect($user->name)->toBe('Renamed')
        ->and($user->password)->toBe($oldHash)
        ->and($user->getRoleNames()->all())->toBe(['manager']);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'role' => 'manager',
        ])
        ->assertRedirect(route('users.index'));

    expect(Hash::check('newpassword', $user->fresh()->password))->toBeTrue();
});

test('updating a user rejects an email taken by someone else', function () {
    $admin = adminTestUser('admin');
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), ['name' => 'X', 'email' => $admin->email, 'role' => 'driver'])
        ->assertSessionHasErrors(['email']);
});

test('an admin can delete other users but not themselves', function () {
    $admin = adminTestUser('admin');
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertSessionHas('success', 'User deleted successfully');
    expect(User::find($user->id))->toBeNull();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertSessionHas('error', 'You cannot delete your own account');
    expect(User::find($admin->id))->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->where('users.data.0.is_self', true));
});
