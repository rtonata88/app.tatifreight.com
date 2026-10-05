<?php

use App\Models\Client;
use App\Models\RateCard;
use App\Models\VehicleType;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see rate cards', function () {
    $this->get(route('rate-cards.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $card = RateCard::factory()->create();

    $this->actingAs($user)->get(route('rate-cards.index'))->assertForbidden();
    $this->actingAs($user)->get(route('rate-cards.create'))->assertForbidden();
    $this->actingAs($user)->patch(route('rate-cards.toggle-active', $card))->assertForbidden();
    $this->actingAs($user)->delete(route('rate-cards.destroy', $card))->assertForbidden();
});

test('rate cards index lists, filters and counts rate cards', function () {
    $user = userWithPermissions(['view-rate-cards', 'edit-rate-cards']);
    $tipper = VehicleType::factory()->create(['name' => 'Tipper']);
    $cooler = VehicleType::factory()->create(['name' => 'Cooler']);
    $client = Client::create(['name' => 'Namib Mills', 'company_name' => 'Namib Mills (Pty) Ltd', 'email' => 'nm@example.com', 'is_active' => true]);

    RateCard::factory()->create(['vehicle_type_id' => $tipper->id, 'name' => 'Tipper Daily', 'rate_type' => 'daily', 'client_id' => $client->id]);
    RateCard::factory()->create(['vehicle_type_id' => $cooler->id, 'name' => 'Cooler Per KM', 'rate_type' => 'per_km']);
    RateCard::factory()->inactive()->create(['vehicle_type_id' => $cooler->id, 'rate_type' => 'hourly']);

    $this->actingAs($user)
        ->get(route('rate-cards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('rate-cards/index')
            ->has('rateCards.data', 3)
            ->has('vehicleTypes', 2)
            ->where('stats.active', 2)
            ->where('stats.inactive', 1)
            ->where('stats.client_specific', 1)
            ->where('stats.general', 2)
            ->where('can.edit', true)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('rate-cards.index', ['vehicle_type' => $cooler->id, 'rate_type' => 'per_km']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rateCards.data', 1)
            ->where('rateCards.data.0.name', 'Cooler Per KM')
            ->where('filters.rate_type', 'per_km')
        );

    $this->actingAs($user)
        ->get(route('rate-cards.index', ['search' => 'Namib Mills']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rateCards.data', 1)
            ->where('rateCards.data.0.client.company_name', 'Namib Mills (Pty) Ltd')
        );
});

test('a rate card can be created', function () {
    $user = userWithPermissions(['view-rate-cards', 'create-rate-cards']);
    $type = VehicleType::factory()->create();

    $this->actingAs($user)
        ->get(route('rate-cards.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('rate-cards/create')
            ->where('defaults.effective_from', now()->format('Y-m-d'))
        );

    $this->actingAs($user)
        ->post(route('rate-cards.store'), [
            'name' => 'Tipper Daily Rate',
            'vehicle_type_id' => $type->id,
            'client_id' => '',
            'rate_type' => 'daily',
            'rate' => '4500.00',
            'includes_mdc' => true,
            'effective_from' => '2025-11-01',
            'effective_to' => '',
            'is_active' => true,
            'notes' => 'Standard',
        ])
        ->assertRedirect(route('rate-cards.index'))
        ->assertSessionHas('success', 'Rate card created successfully!');

    $card = RateCard::firstOrFail();
    expect($card)
        ->name->toBe('Tipper Daily Rate')
        ->client_id->toBeNull()
        ->effective_to->toBeNull()
        ->includes_mdc->toBeTrue()
        ->is_active->toBeTrue();
    expect((float) $card->rate)->toBe(4500.0);
});

test('creating a rate card validates input', function () {
    $user = userWithPermissions(['create-rate-cards']);

    $this->actingAs($user)
        ->post(route('rate-cards.store'), [
            'vehicle_type_id' => 999,
            'rate_type' => 'weekly',
            'rate' => '0',
            'effective_from' => '2025-11-10',
            'effective_to' => '2025-11-01',
        ])
        ->assertSessionHasErrors(['name', 'vehicle_type_id', 'rate_type', 'rate', 'effective_to']);
});

test('a rate card can be updated', function () {
    $user = userWithPermissions(['view-rate-cards', 'edit-rate-cards']);
    $card = RateCard::factory()->create(['includes_mdc' => true]);

    $this->actingAs($user)
        ->get(route('rate-cards.edit', $card))
        ->assertInertia(fn (Assert $page) => $page->component('rate-cards/edit')->where('rateCard.id', $card->id));

    $this->actingAs($user)
        ->put(route('rate-cards.update', $card), [
            'name' => 'Renamed',
            'vehicle_type_id' => $card->vehicle_type_id,
            'rate_type' => 'tonnage',
            'rate' => '12.50',
            'includes_mdc' => false,
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
            'is_active' => false,
        ])
        ->assertRedirect(route('rate-cards.index'))
        ->assertSessionHas('success', 'Rate card updated successfully!');

    expect($card->fresh())
        ->name->toBe('Renamed')
        ->rate_type->toBe('tonnage')
        ->includes_mdc->toBeFalse()
        ->is_active->toBeFalse()
        ->effective_to->format('Y-m-d')->toBe('2025-12-31');
});

test('a rate card can be toggled and deleted', function () {
    $user = userWithPermissions(['view-rate-cards', 'edit-rate-cards', 'delete-rate-cards']);
    $card = RateCard::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->patch(route('rate-cards.toggle-active', $card))
        ->assertSessionHas('success', 'Rate card status updated');
    expect($card->fresh()->is_active)->toBeFalse();

    $this->actingAs($user)->patch(route('rate-cards.toggle-active', $card));
    expect($card->fresh()->is_active)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('rate-cards.destroy', $card))
        ->assertSessionHas('success', 'Rate card deleted successfully');
    expect(RateCard::find($card->id))->toBeNull();
});
