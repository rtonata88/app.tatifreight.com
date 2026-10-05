<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Logbook;
use App\Models\MdcCalculation;
use App\Models\MdcRateCard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected and users without permission are forbidden', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('vehicles.show', $vehicle))->assertRedirect(route('login'));

    $this->actingAs(userWithPermissions([]))
        ->get(route('vehicles.show', $vehicle))
        ->assertForbidden();
});

test('vehicle page shows details, recent records and statistics', function () {
    $user = userWithPermissions(['view-vehicles']);
    $vehicle = Vehicle::factory()->create([
        'reg_number' => 'N 4321 W',
        'status' => 'in_use',
        'gvm_tonnes' => null,
        'insurance_expiry' => now()->subDay()->toDateString(),
        'disc_expiry' => now()->addDays(10)->toDateString(),
        'roadworthy_expiry' => now()->addYear()->toDateString(),
        'current_mileage' => 0,
    ]);

    $client = Client::create(['name' => 'Jane Doe', 'company_name' => 'Acme Logistics', 'email' => 'acme@example.com']);
    Booking::create([
        'booking_number' => 'BK-0001',
        'client_id' => $client->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'confirmed',
        'start_date' => now()->subDays(3),
        'end_date' => now()->subDay(),
    ]);

    // Without GVM no MDC is created automatically, so add MDC rows by hand.
    $logbook = Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'start_odometer' => 100, 'end_odometer' => 400]);
    Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'start_odometer' => 400, 'end_odometer' => 500]);

    MdcCalculation::create([
        'logbook_id' => $logbook->id,
        'vehicle_id' => $vehicle->id,
        'distance_km' => 300,
        'mdc_amount' => 750,
        'amount_paid' => 250,
        'payment_status' => 'partially_paid',
        'calculation_date' => now()->toDateString(),
    ]);

    $staff = User::factory()->create();
    Expense::create(['vehicle_id' => $vehicle->id, 'user_id' => $staff->id, 'category' => 'fuel', 'amount' => 1200.50, 'expense_date' => now()->toDateString(), 'status' => 'approved']);
    Expense::create(['vehicle_id' => $vehicle->id, 'user_id' => $staff->id, 'category' => 'fuel', 'amount' => 999, 'expense_date' => now()->toDateString(), 'status' => 'pending']);

    VehicleInspection::factory()->failed()->create(['vehicle_id' => $vehicle->id, 'inspection_type' => 'annual']);

    $this->actingAs($user)
        ->get(route('vehicles.show', $vehicle))
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/show')
            ->where('vehicle.reg_number', 'N 4321 W')
            ->where('vehicle.status', 'in_use')
            ->where('vehicle.insurance_expiry.past', true)
            ->where('vehicle.disc_expiry.soon', true)
            ->where('vehicle.disc_expiry.past', false)
            ->where('vehicle.roadworthy_expiry.soon', false)
            ->where('vehicle.next_service_date', null)
            ->where('vehicle.mdc_rate_card', null)
            ->has('bookings', 1)
            ->where('bookings.0.client', 'Acme Logistics')
            ->where('bookings.0.status', 'confirmed')
            ->has('logbooks', 2)
            ->has('mdcCalculations', 1)
            ->where('mdcCalculations.0.payment_status', 'partially_paid')
            ->has('inspections', 1)
            ->where('inspections.0.passed', false)
            ->where('stats.total_bookings', 1)
            ->where('stats.total_mdc_charges', 750)
            ->where('stats.total_mdc_paid', 250)
            ->where('stats.total_mdc_outstanding', 500)
            ->where('stats.total_expenses', 1200.5)
            ->where('stats.total_distance', 400)
            ->where('can.edit', false)
        );
});

test('vehicle page shows the linked or suggested MDC rate card', function () {
    $user = userWithPermissions(['view-vehicles', 'edit-vehicles']);
    $card = MdcRateCard::create([
        'category_name' => 'Heavy (16t+)',
        'min_gvm_tonnes' => 16,
        'max_gvm_tonnes' => null,
        'rate_per_100km' => 312.5,
        'effective_from' => now()->subYear()->toDateString(),
        'is_active' => true,
    ]);

    $suggested = Vehicle::factory()->create(['gvm_tonnes' => 25]);
    $linked = Vehicle::factory()->create(['gvm_tonnes' => 25, 'mdc_rate_card_id' => $card->id]);

    $this->actingAs($user)
        ->get(route('vehicles.show', $suggested))
        ->assertInertia(fn (Assert $page) => $page
            ->where('vehicle.mdc_rate_card.category_name', 'Heavy (16t+)')
            ->where('vehicle.mdc_rate_card.rate_per_100km', 312.5)
            ->where('vehicle.mdc_rate_card.suggested', true)
            ->where('can.edit', true)
        );

    $this->actingAs($user)
        ->get(route('vehicles.show', $linked))
        ->assertInertia(fn (Assert $page) => $page->where('vehicle.mdc_rate_card.suggested', false));
});
