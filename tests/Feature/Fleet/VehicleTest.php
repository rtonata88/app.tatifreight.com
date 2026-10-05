<?php

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see vehicles', function () {
    $this->get(route('vehicles.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $this->actingAs(userWithPermissions([]))
        ->get(route('vehicles.index'))
        ->assertForbidden();
});

test('vehicles index lists and filters vehicles', function () {
    $user = userWithPermissions(['view-vehicles', 'delete-vehicles']);
    $type = VehicleType::factory()->create();
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'reg_number' => 'N 1234 W', 'status' => 'available']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'reg_number' => 'N 9999 W', 'status' => 'retired']);

    $this->actingAs($user)
        ->get(route('vehicles.index', ['status' => 'retired']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.reg_number', 'N 9999 W')
            ->where('filters.status', 'retired')
            ->where('can.delete', true)
            ->where('can.create', false)
        );
});

test('a vehicle can be created with uploads', function () {
    Storage::fake('public');
    $user = userWithPermissions(['view-vehicles', 'create-vehicles']);
    $type = VehicleType::factory()->create();

    $this->actingAs($user)
        ->post(route('vehicles.store'), [
            'vehicle_type_id' => $type->id,
            'reg_number' => 'N 5555 W',
            'make' => 'Scania',
            'model' => 'R500',
            'status' => 'available',
            'license_disc_upload' => UploadedFile::fake()->image('disc.jpg'),
        ])
        ->assertRedirect(route('vehicles.index'))
        ->assertSessionHas('success', 'Vehicle created successfully!');

    $vehicle = Vehicle::where('reg_number', 'N 5555 W')->firstOrFail();
    expect((float) $vehicle->current_mileage)->toBe(0.0);
    Storage::disk('public')->assertExists($vehicle->license_disc_path);
});

test('creating a vehicle validates required fields and unique reg number', function () {
    $user = userWithPermissions(['create-vehicles']);
    $existing = Vehicle::factory()->create();

    $this->actingAs($user)
        ->post(route('vehicles.store'), ['reg_number' => $existing->reg_number])
        ->assertSessionHasErrors(['vehicle_type_id', 'reg_number', 'make', 'model']);
});

test('a vehicle can be updated and deleted', function () {
    $user = userWithPermissions(['view-vehicles', 'edit-vehicles', 'delete-vehicles']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)
        ->get(route('vehicles.edit', $vehicle))
        ->assertInertia(fn (Assert $page) => $page->component('vehicles/edit')->where('vehicle.id', $vehicle->id));

    $this->actingAs($user)
        ->put(route('vehicles.update', $vehicle), [
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'reg_number' => $vehicle->reg_number,
            'make' => 'Volvo',
            'model' => 'FH16',
            'status' => 'maintenance',
        ])
        ->assertRedirect(route('vehicles.index'));

    expect($vehicle->fresh())->make->toBe('Volvo')->status->toBe('maintenance');

    $this->actingAs($user)->delete(route('vehicles.destroy', $vehicle))->assertSessionHas('success');
    expect(Vehicle::find($vehicle->id))->toBeNull();
});

test('updating a vehicle to another vehicle\'s registration is a validation error', function () {
    $user = userWithPermissions(['edit-vehicles']);
    [$a, $b] = Vehicle::factory()->count(2)->create();

    $this->actingAs($user)
        ->put(route('vehicles.update', $a), [
            'vehicle_type_id' => $a->vehicle_type_id,
            'reg_number' => $b->reg_number,
            'make' => 'Volvo',
            'model' => 'FH',
            'status' => 'available',
        ])
        ->assertSessionHasErrors('reg_number');
});
