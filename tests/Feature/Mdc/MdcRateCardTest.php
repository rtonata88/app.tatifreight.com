<?php

use App\Models\MdcRateCard;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see mdc rates', function () {
    $this->get(route('mdc-rates.index'))->assertRedirect(route('login'));
});

test('users without manage-mdc-rates are forbidden', function () {
    $user = userWithPermissions(['create-vehicles', 'edit-vehicles', 'delete-vehicles']);
    $rate = MdcRateCard::factory()->create();

    $this->actingAs($user)->get(route('mdc-rates.index'))->assertForbidden();
    $this->actingAs($user)->post(route('mdc-rates.store'))->assertForbidden();
    $this->actingAs($user)->delete(route('mdc-rates.destroy', $rate))->assertForbidden();
});

test('index lists, searches and filters rates with counts', function () {
    $user = userWithPermissions(['manage-mdc-rates', 'edit-vehicles']);
    MdcRateCard::factory()->create(['category_name' => 'Heavy Truck', 'min_gvm_tonnes' => 24]);
    MdcRateCard::factory()->inactive()->create(['category_name' => 'Light Truck', 'min_gvm_tonnes' => 3.5]);

    $this->actingAs($user)
        ->get(route('mdc-rates.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('mdc-rates/index')
            ->has('mdcRates.data', 2)
            ->where('mdcRates.data.0.category_name', 'Light Truck')
            ->where('totalRates', 2)
            ->where('activeRates', 1)
            ->where('filters.status', 'all')
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('mdc-rates.index', ['status' => 'inactive']))
        ->assertInertia(fn (Assert $page) => $page->has('mdcRates.data', 1)->where('mdcRates.data.0.category_name', 'Light Truck'));

    $this->actingAs($user)
        ->get(route('mdc-rates.index', ['search' => 'heavy']))
        ->assertInertia(fn (Assert $page) => $page->has('mdcRates.data', 1)->where('mdcRates.data.0.category_name', 'Heavy Truck'));
});

test('a rate can be created', function () {
    $user = userWithPermissions(['manage-mdc-rates', 'create-vehicles']);

    $this->actingAs($user)
        ->get(route('mdc-rates.create'))
        ->assertInertia(fn (Assert $page) => $page->component('mdc-rates/create')->where('defaultEffectiveFrom', now()->format('Y-m-d')));

    $this->actingAs($user)
        ->post(route('mdc-rates.store'), [
            'category_name' => 'Heavy Truck (24t - 27t)',
            'min_gvm_tonnes' => '24',
            'max_gvm_tonnes' => '',
            'rate_per_100km' => '350.50',
            'effective_from' => '2025-01-01',
            'effective_to' => '',
            'is_active' => true,
            'notes' => 'RFANAM 2025',
        ])
        ->assertRedirect(route('mdc-rates.index'))
        ->assertSessionHas('success', 'MDC rate card created successfully!');

    $rate = MdcRateCard::firstOrFail();
    expect($rate->category_name)->toBe('Heavy Truck (24t - 27t)')
        ->and($rate->max_gvm_tonnes)->toBeNull()
        ->and($rate->effective_to)->toBeNull()
        ->and((float) $rate->rate_per_100km)->toBe(350.5)
        ->and($rate->is_active)->toBeTrue();
});

test('creating a rate validates the ranges', function () {
    $user = userWithPermissions(['manage-mdc-rates', 'create-vehicles']);

    $this->actingAs($user)
        ->post(route('mdc-rates.store'), [
            'category_name' => '',
            'min_gvm_tonnes' => '10',
            'max_gvm_tonnes' => '5',
            'rate_per_100km' => '-1',
            'effective_from' => '2025-06-01',
            'effective_to' => '2025-01-01',
        ])
        ->assertSessionHasErrors(['category_name', 'max_gvm_tonnes', 'rate_per_100km', 'effective_to']);
});

test('without create-vehicles the create screen redirects back to the list', function () {
    $user = userWithPermissions(['manage-mdc-rates']);

    $this->actingAs($user)->get(route('mdc-rates.create'))->assertRedirect(route('mdc-rates.index'));
    $this->actingAs($user)->post(route('mdc-rates.store'), ['category_name' => 'X'])->assertRedirect(route('mdc-rates.index'));
    expect(MdcRateCard::count())->toBe(0);
});

test('a rate can be edited', function () {
    $user = userWithPermissions(['manage-mdc-rates', 'edit-vehicles']);
    $rate = MdcRateCard::factory()->create(['category_name' => 'Old', 'max_gvm_tonnes' => null]);

    $this->actingAs($user)
        ->get(route('mdc-rates.edit', $rate))
        ->assertInertia(fn (Assert $page) => $page
            ->component('mdc-rates/edit')
            ->where('mdcRateCard.id', $rate->id)
            ->where('mdcRateCard.max_gvm_tonnes', '')
        );

    $this->actingAs($user)
        ->put(route('mdc-rates.update', $rate), [
            'category_name' => 'New',
            'min_gvm_tonnes' => '9',
            'max_gvm_tonnes' => '16',
            'rate_per_100km' => '120',
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
            'is_active' => false,
        ])
        ->assertRedirect(route('mdc-rates.index'))
        ->assertSessionHas('success', 'MDC rate card updated successfully!');

    $rate->refresh();
    expect($rate->category_name)->toBe('New')
        ->and((float) $rate->max_gvm_tonnes)->toBe(16.0)
        ->and($rate->effective_to->format('Y-m-d'))->toBe('2025-12-31')
        ->and($rate->is_active)->toBeFalse();
});

test('without edit-vehicles the edit screen redirects back to the list', function () {
    $user = userWithPermissions(['manage-mdc-rates']);
    $rate = MdcRateCard::factory()->create();

    $this->actingAs($user)->get(route('mdc-rates.edit', $rate))->assertRedirect(route('mdc-rates.index'));
});

test('status can be toggled with edit permission', function () {
    $rate = MdcRateCard::factory()->create(['is_active' => true]);

    $this->actingAs(userWithPermissions(['manage-mdc-rates']))
        ->patch(route('mdc-rates.toggle-status', $rate))
        ->assertSessionHas('error', 'You do not have permission to edit MDC rates');
    expect($rate->fresh()->is_active)->toBeTrue();

    $this->actingAs(userWithPermissions(['manage-mdc-rates', 'edit-vehicles']))
        ->patch(route('mdc-rates.toggle-status', $rate))
        ->assertSessionHas('success', 'MDC rate status updated');
    expect($rate->fresh()->is_active)->toBeFalse();
});

test('a rate can be deleted with delete permission', function () {
    $rate = MdcRateCard::factory()->create();

    $this->actingAs(userWithPermissions(['manage-mdc-rates']))
        ->delete(route('mdc-rates.destroy', $rate))
        ->assertSessionHas('error', 'You do not have permission to delete MDC rates');
    expect(MdcRateCard::find($rate->id))->not->toBeNull();

    $this->actingAs(userWithPermissions(['manage-mdc-rates', 'delete-vehicles']))
        ->delete(route('mdc-rates.destroy', $rate))
        ->assertSessionHas('success', 'MDC rate deleted successfully');
    expect(MdcRateCard::find($rate->id))->toBeNull();
});
