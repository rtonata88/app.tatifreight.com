<?php

require_once __DIR__.'/helpers.php';

use App\Models\CompanyBankAccount;
use Inertia\Testing\AssertableInertia as Assert;

$payload = fn (array $overrides = []) => [
    'bank_name' => 'FNB Namibia',
    'account_name' => 'Tati Investment CC',
    'account_number' => '62000000000',
    'branch_name' => 'Katutura',
    'branch_code' => '280172',
    'swift_code' => '',
    'currency' => 'NAD',
    'is_primary' => false,
    ...$overrides,
];

test('guests cannot see bank accounts', function () {
    $this->get(route('settings.bank-accounts'))->assertRedirect(route('login'));
});

test('non-admins are forbidden from bank accounts', function () use ($payload) {
    $user = adminTestUser('accountant');

    $this->actingAs($user)->get(route('settings.bank-accounts'))->assertForbidden();
    $this->actingAs($user)->post(route('settings.bank-accounts.store'), $payload())->assertForbidden();
});

test('bank accounts are listed primary first', function () {
    $admin = adminTestUser('admin');
    CompanyBankAccount::factory()->create(['bank_name' => 'Other']);
    CompanyBankAccount::factory()->primary()->create(['bank_name' => 'Main']);

    $this->actingAs($admin)
        ->get(route('settings.bank-accounts'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/bank-accounts')
            ->has('bankAccounts', 2)
            ->where('bankAccounts.0.bank_name', 'Main')
            ->where('bankAccounts.0.is_primary', true)
            ->where('bankAccounts.1.is_active', true)
        );
});

test('the first bank account becomes primary automatically', function () use ($payload) {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->post(route('settings.bank-accounts.store'), $payload())
        ->assertSessionHas('success', 'Bank account added successfully!');

    expect(CompanyBankAccount::first()->is_primary)->toBeTrue();

    $this->actingAs($admin)->post(route('settings.bank-accounts.store'), $payload(['bank_name' => 'Second']));
    expect(CompanyBankAccount::where('bank_name', 'Second')->first()->is_primary)->toBeFalse();
});

test('adding a primary account unsets the previous primary', function () use ($payload) {
    $admin = adminTestUser('admin');
    $old = CompanyBankAccount::factory()->primary()->create();

    $this->actingAs($admin)->post(route('settings.bank-accounts.store'), $payload(['is_primary' => true]));

    expect($old->fresh()->is_primary)->toBeFalse()
        ->and(CompanyBankAccount::where('is_primary', true)->count())->toBe(1);
});

test('bank account validation', function () use ($payload) {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->post(route('settings.bank-accounts.store'), $payload(['bank_name' => '', 'account_name' => '', 'account_number' => '', 'currency' => '']))
        ->assertSessionHasErrors(['bank_name', 'account_name', 'account_number', 'currency']);
});

test('a bank account can be updated', function () use ($payload) {
    $admin = adminTestUser('admin');
    $account = CompanyBankAccount::factory()->create();

    $this->actingAs($admin)
        ->put(route('settings.bank-accounts.update', $account), $payload(['bank_name' => 'Nedbank', 'currency' => 'ZAR']))
        ->assertSessionHas('success', 'Bank account updated successfully!');

    expect($account->fresh())->bank_name->toBe('Nedbank')->currency->toBe('ZAR');
});

test('set primary moves the primary flag', function () {
    $admin = adminTestUser('admin');
    $primary = CompanyBankAccount::factory()->primary()->create();
    $other = CompanyBankAccount::factory()->create();

    $this->actingAs($admin)
        ->patch(route('settings.bank-accounts.primary', $other))
        ->assertSessionHas('success', 'Primary account updated!');

    expect($other->fresh()->is_primary)->toBeTrue()->and($primary->fresh()->is_primary)->toBeFalse();
});

test('toggle active refuses the active primary account', function () {
    $admin = adminTestUser('admin');
    $primary = CompanyBankAccount::factory()->primary()->create();
    $other = CompanyBankAccount::factory()->create();

    $this->actingAs($admin)
        ->patch(route('settings.bank-accounts.toggle-active', $primary))
        ->assertSessionHas('error', 'Cannot deactivate primary account. Set another account as primary first.');
    expect($primary->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('settings.bank-accounts.toggle-active', $other))
        ->assertSessionHas('success', 'Account deactivated!');
    expect($other->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('settings.bank-accounts.toggle-active', $other))
        ->assertSessionHas('success', 'Account activated!');
    expect($other->fresh()->is_active)->toBeTrue();
});

test('the primary account cannot be deleted but others can', function () {
    $admin = adminTestUser('admin');
    $primary = CompanyBankAccount::factory()->primary()->create();
    $other = CompanyBankAccount::factory()->create();

    $this->actingAs($admin)
        ->delete(route('settings.bank-accounts.destroy', $primary))
        ->assertSessionHas('error', 'Cannot delete primary account. Set another account as primary first.');
    expect(CompanyBankAccount::find($primary->id))->not->toBeNull();

    $this->actingAs($admin)
        ->delete(route('settings.bank-accounts.destroy', $other))
        ->assertSessionHas('success', 'Bank account deleted!');
    expect(CompanyBankAccount::find($other->id))->toBeNull();
});
