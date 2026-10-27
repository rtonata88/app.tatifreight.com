<?php

require_once __DIR__.'/helpers.php';

use App\Models\CompanySetting;
use Inertia\Testing\AssertableInertia as Assert;

test('non-admins are forbidden from fuel settings', function () {
    $this->actingAs(adminTestUser('accountant'))->get(route('settings.fuel'))->assertForbidden();
});

test('fuel settings page shows the price and thresholds', function () {
    CompanySetting::get()->update(['fuel_price_per_litre' => 21.45, 'fuel_consumption_tolerance' => 20]);

    $this->actingAs(adminTestUser('admin'))
        ->get(route('settings.fuel'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/fuel')
            ->where('settings.fuel_price_per_litre', 21.45)
            ->where('settings.fuel_consumption_tolerance', 20)
        );
});

test('fuel thresholds are saved and a cleared threshold keeps its value', function () {
    CompanySetting::get()->update(['fuel_min_km_between_fills' => 150]);

    $this->actingAs(adminTestUser('admin'))
        ->put(route('settings.fuel.update'), ['fuel_consumption_tolerance' => 25, 'fuel_min_km_between_fills' => ''])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Fuel settings updated successfully!');

    $settings = CompanySetting::first();
    expect($settings->fuel_consumption_tolerance)->toBe(25)->and($settings->fuel_min_km_between_fills)->toBe(150);
});

test('saving company settings leaves the fuel settings alone', function () {
    CompanySetting::get()->update(['fuel_price_per_litre' => 21.45]);

    $this->actingAs(adminTestUser('admin'))
        ->put(route('settings.company.update'), ['company_name' => 'Tati'])
        ->assertSessionHasNoErrors();

    expect((float) CompanySetting::first()->fuel_price_per_litre)->toBe(21.45);
});
