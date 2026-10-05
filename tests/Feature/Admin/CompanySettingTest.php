<?php

require_once __DIR__.'/helpers.php';

use App\Models\CompanyBankAccount;
use App\Models\CompanySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see company settings', function () {
    $this->get(route('settings.company'))->assertRedirect(route('login'));
});

test('non-admins are forbidden from company settings', function () {
    $this->actingAs(adminTestUser('accountant'))->get(route('settings.company'))->assertForbidden();
});

test('company settings page shows defaults and active bank accounts', function () {
    $admin = adminTestUser('admin');
    CompanyBankAccount::factory()->primary()->create(['bank_name' => 'Bank Windhoek']);
    CompanyBankAccount::factory()->inactive()->create(['bank_name' => 'Closed Bank']);

    $this->actingAs($admin)
        ->get(route('settings.company'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/company')
            ->where('settings.company_name', 'TAATI Transport')
            ->where('settings.country', 'Namibia')
            ->where('settings.logo_url', null)
            ->has('bankAccounts', 1)
            ->where('bankAccounts.0.bank_name', 'Bank Windhoek')
            ->where('bankAccounts.0.is_primary', true)
        );
});

test('company settings can be updated with logo and signature uploads', function () {
    Storage::fake('public');
    $admin = adminTestUser('admin');
    $old = UploadedFile::fake()->image('old.png')->store('company', 'public');
    CompanySetting::get()->update(['logo_path' => $old]);

    $this->actingAs($admin)
        ->post(route('settings.company.update'), [
            '_method' => 'put',
            'company_name' => 'Tati Investment CC',
            'vat_number' => '123456789',
            'website' => 'https://taati.com.na',
            'city' => 'Windhoek',
            'logo_upload' => UploadedFile::fake()->image('logo.png'),
            'signature_upload' => UploadedFile::fake()->image('sig.png'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Company settings updated successfully!');

    $settings = CompanySetting::first();
    expect($settings->company_name)->toBe('Tati Investment CC')
        ->and($settings->city)->toBe('Windhoek')
        ->and($settings->logo_path)->not->toBe($old);
    Storage::disk('public')->assertExists([$settings->logo_path, $settings->signature_path]);
    Storage::disk('public')->assertMissing($old);
});

test('company settings validation matches the old rules', function () {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)
        ->put(route('settings.company.update'), [
            'company_name' => '',
            'email' => 'not-an-email',
            'website' => 'not a url',
            'postal_code' => str_repeat('9', 21),
            'logo_upload' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['company_name', 'email', 'website', 'postal_code', 'logo_upload']);
});

test('logo and signature can be deleted', function () {
    Storage::fake('public');
    $admin = adminTestUser('admin');
    $logo = UploadedFile::fake()->image('logo.png')->store('company', 'public');
    $signature = UploadedFile::fake()->image('sig.png')->store('company', 'public');
    CompanySetting::get()->update(['logo_path' => $logo, 'signature_path' => $signature]);

    $this->actingAs($admin)
        ->delete(route('settings.company.logo.destroy'))
        ->assertSessionHas('success', 'Logo deleted successfully');
    $this->actingAs($admin)
        ->delete(route('settings.company.signature.destroy'))
        ->assertSessionHas('success', 'Signature deleted successfully');

    $settings = CompanySetting::first();
    expect($settings->logo_path)->toBeNull()->and($settings->signature_path)->toBeNull();
    Storage::disk('public')->assertMissing([$logo, $signature]);
});
