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
            'vehicle_type' => $type->name,
            'reg_number' => 'N 5555 W',
            'make' => 'Scania',
            'model' => 'R500',
            'status' => 'available',
            'license_disc_upload' => UploadedFile::fake()->image('disc.jpg'),
            'registration_certificate_upload' => UploadedFile::fake()->image('registration.jpg'),
        ])
        ->assertRedirect(route('vehicles.index'))
        ->assertSessionHas('success', 'Vehicle created successfully!');

    $vehicle = Vehicle::where('reg_number', 'N 5555 W')->firstOrFail();
    expect($vehicle->vehicle_type_id)->toBe($type->id);
    expect((float) $vehicle->current_mileage)->toBe(0.0);
    Storage::disk('public')->assertExists([$vehicle->license_disc_path, $vehicle->registration_certificate_path]);
});

test('creating a vehicle validates required fields and unique reg number', function () {
    $user = userWithPermissions(['create-vehicles']);
    $existing = Vehicle::factory()->create();

    $this->actingAs($user)
        ->post(route('vehicles.store'), ['reg_number' => $existing->reg_number])
        ->assertSessionHasErrors(['vehicle_type', 'reg_number', 'make', 'model']);
});

test('a vehicle can be updated and deleted', function () {
    $user = userWithPermissions(['view-vehicles', 'edit-vehicles', 'delete-vehicles']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)
        ->get(route('vehicles.edit', $vehicle))
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/edit')
            ->where('vehicle.id', $vehicle->id)
            ->where('vehicle.vehicle_type', $vehicle->vehicleType->name)
        );

    $this->actingAs($user)
        ->put(route('vehicles.update', $vehicle), [
            'vehicle_type' => $vehicle->vehicleType->name,
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
            'vehicle_type' => $a->vehicleType->name,
            'reg_number' => $b->reg_number,
            'make' => 'Volvo',
            'model' => 'FH',
            'status' => 'available',
        ])
        ->assertSessionHasErrors('reg_number');
});

test('a typed vehicle type matches an existing one ignoring case, or creates a new one', function () {
    $user = userWithPermissions(['create-vehicles']);
    $tipper = VehicleType::factory()->create(['name' => 'Tipper Truck']);
    $payload = ['make' => 'Scania', 'model' => 'R500', 'status' => 'available'];

    $this->actingAs($user)
        ->post(route('vehicles.store'), [...$payload, 'vehicle_type' => '  tipper truck ', 'reg_number' => 'N 1 W'])
        ->assertRedirect(route('vehicles.index'));

    $this->actingAs($user)
        ->post(route('vehicles.store'), [...$payload, 'vehicle_type' => 'Flatbed Trailer', 'reg_number' => 'N 2 W'])
        ->assertRedirect(route('vehicles.index'));

    expect(Vehicle::where('reg_number', 'N 1 W')->value('vehicle_type_id'))->toBe($tipper->id);

    $flatbed = VehicleType::where('name', 'Flatbed Trailer')->firstOrFail();
    expect(Vehicle::where('reg_number', 'N 2 W')->value('vehicle_type_id'))->toBe($flatbed->id)
        ->and($flatbed->base_rate_daily)->toBeNull()
        ->and(VehicleType::count())->toBe(2);
});

test('the vehicle form suggests existing type names', function () {
    VehicleType::factory()->create(['name' => 'Tipper Truck']);
    VehicleType::factory()->create(['name' => 'Cooler Truck']);

    $this->actingAs(userWithPermissions(['create-vehicles']))
        ->get(route('vehicles.create'))
        ->assertInertia(fn (Assert $page) => $page->where('vehicleTypes', ['Cooler Truck', 'Tipper Truck']));
});
