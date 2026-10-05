<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\Expense;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function expenseTestBooking(array $attributes = []): Booking
{
    $client = Client::create([
        'name' => 'Acme Client',
        'company_name' => 'Acme Ltd',
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
    ]);

    return Booking::create([
        'booking_number' => 'BK-'.fake()->unique()->numerify('#####'),
        'client_id' => $client->id,
        'vehicle_id' => Vehicle::factory()->create()->id,
        'status' => 'confirmed',
        'start_date' => now(),
        'end_date' => now()->addDay(),
        ...$attributes,
    ]);
}

test('guests cannot see expenses', function () {
    $this->get(route('expenses.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $expense = Expense::factory()->create();

    $this->actingAs($user)->get(route('expenses.index'))->assertForbidden();
    $this->actingAs($user)->get(route('expenses.show', $expense))->assertForbidden();
    $this->actingAs($user)->post(route('expenses.store'))->assertForbidden();
    $this->actingAs($user)->post(route('expenses.approve', $expense))->assertForbidden();
});

test('expenses index lists, filters and summarises expenses', function () {
    $user = userWithPermissions(['view-expenses', 'edit-expenses']);
    $submitter = User::factory()->create(['name' => 'Jane Driver']);
    Expense::factory()->create(['user_id' => $submitter->id, 'category' => 'fuel', 'status' => 'pending', 'amount' => 100.50, 'description' => 'Diesel top-up']);
    Expense::factory()->create(['category' => 'tolls', 'status' => 'pending', 'amount' => 200]);
    Expense::factory()->approved()->create(['category' => 'fuel', 'amount' => 300.25]);
    Expense::factory()->rejected()->create(['amount' => 999]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/index')
            ->has('expenses.data', 4)
            ->where('stats.pending', 2)
            ->where('stats.approved', 1)
            ->where('stats.rejected', 1)
            ->where('stats.total_pending', 300.5)
            ->where('stats.total_approved', 300.25)
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('expenses.index', ['category' => 'fuel', 'status' => 'pending']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.description', 'Diesel top-up')
            ->where('expenses.data.0.submitted_by', 'Jane Driver')
            ->where('filters.category', 'fuel')
        );

    $this->actingAs($user)
        ->get(route('expenses.index', ['search' => 'Jane']))
        ->assertInertia(fn (Assert $page) => $page->has('expenses.data', 1));
});

test('search combined with a status filter does not leak other statuses', function () {
    $user = userWithPermissions(['view-expenses']);
    Expense::factory()->create(['description' => 'Fuel run', 'status' => 'pending']);
    Expense::factory()->approved()->create(['description' => 'Fuel run', 'status' => 'approved']);

    $this->actingAs($user)
        ->get(route('expenses.index', ['search' => 'Fuel', 'status' => 'approved']))
        ->assertInertia(fn (Assert $page) => $page->has('expenses.data', 1)->where('expenses.data.0.status', 'approved'));
});

test('drivers only see their own expenses and stats', function () {
    // userWithRole() runs the seeder, which needs a console command; build the role directly.
    $driver = userWithPermissions(['view-expenses', 'create-expenses']);
    $driver->assignRole(Spatie\Permission\Models\Role::findOrCreate('driver', 'web'));
    Expense::factory()->create(['user_id' => $driver->id, 'amount' => 50]);
    Expense::factory()->count(2)->create();

    $this->actingAs($driver)
        ->get(route('expenses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses.data', 1)
            ->where('stats.pending', 1)
            ->where('stats.total_pending', 50)
        );
});

test('an expense can be submitted with a receipt', function () {
    Storage::fake('public');
    $user = userWithPermissions(['view-expenses', 'create-expenses']);
    $vehicle = Vehicle::factory()->create();
    $booking = expenseTestBooking();

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/create')
            ->where('defaults.expense_date', now()->format('Y-m-d'))
            ->has('bookings', 1)
        );

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'category' => 'fuel',
            'amount' => '1250.75',
            'expense_date' => '2025-11-04',
            'description' => 'Diesel for Walvis trip',
            'vehicle_id' => $vehicle->id,
            'booking_id' => $booking->id,
            'notes' => 'Paid cash',
            'receipt_upload' => UploadedFile::fake()->image('slip.jpg'),
        ])
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success', 'Expense submitted successfully!');

    $expense = Expense::firstOrFail();
    expect($expense)
        ->user_id->toBe($user->id)
        ->status->toBe('pending')
        ->vehicle_id->toBe($vehicle->id)
        ->booking_id->toBe($booking->id);
    expect((float) $expense->amount)->toBe(1250.75);
    expect($expense->receipt_path)->toStartWith('expenses/receipts/');
    Storage::disk('public')->assertExists($expense->receipt_path);
});

test('submitting an expense validates input', function () {
    $user = userWithPermissions(['create-expenses']);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'category' => 'mdc_payment',
            'amount' => '0',
            'description' => str_repeat('x', 501),
            'vehicle_id' => 999,
            'receipt_upload' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['category', 'amount', 'expense_date', 'description', 'vehicle_id', 'receipt_upload']);

    expect(Expense::count())->toBe(0);
});

test('the show page renders expense details and receipt info', function () {
    Storage::fake('public');
    Storage::disk('public')->put('expenses/receipts/r.png', 'x');
    $user = userWithPermissions(['view-expenses', 'edit-expenses']);
    $booking = expenseTestBooking();
    $expense = Expense::factory()->approved()->create([
        'booking_id' => $booking->id,
        'receipt_path' => 'expenses/receipts/r.png',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.show', $expense))
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/show')
            ->where('expense.id', $expense->id)
            ->where('expense.booking.client', 'Acme Ltd')
            ->where('expense.has_receipt', true)
            ->where('expense.receipt_is_image', true)
            ->where('can.edit', true)
            ->where('can.delete', false)
        );
});

test('pending expenses can be approved and rejected with edit permission', function () {
    $user = userWithPermissions(['view-expenses', 'edit-expenses']);
    $a = Expense::factory()->create();
    $b = Expense::factory()->create();

    $this->actingAs($user)->post(route('expenses.approve', $a))->assertSessionHas('success', 'Expense approved successfully');
    $this->actingAs($user)->post(route('expenses.reject', $b))->assertSessionHas('success', 'Expense rejected');

    expect($a->fresh())->status->toBe('approved')->approved_by->toBe($user->id)->approved_at->not->toBeNull();
    expect($b->fresh())->status->toBe('rejected')->approved_by->toBe($user->id);
});

test('approve-expenses alone does not allow approving (old screens checked edit-expenses)', function () {
    $user = userWithPermissions(['view-expenses', 'approve-expenses']);
    $expense = Expense::factory()->create();

    $this->actingAs($user)->post(route('expenses.approve', $expense))->assertForbidden();
    expect($expense->fresh()->status)->toBe('pending');
});

test('an expense can be updated and approval is recorded on status change', function () {
    Storage::fake('public');
    Storage::disk('public')->put('expenses/receipts/old.png', 'x');
    $user = userWithPermissions(['view-expenses', 'edit-expenses']);
    $expense = Expense::factory()->create(['receipt_path' => 'expenses/receipts/old.png']);

    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertInertia(fn (Assert $page) => $page->component('expenses/edit')->where('expense.id', $expense->id));

    $this->actingAs($user)
        ->post(route('expenses.update', $expense), [
            '_method' => 'put',
            'category' => 'repairs',
            'amount' => '480',
            'expense_date' => '2025-11-10',
            'description' => 'Brake pads',
            'status' => 'approved',
            'receipt_upload' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success', 'Expense updated successfully!');

    $expense->refresh();
    expect($expense)
        ->category->toBe('repairs')
        ->status->toBe('approved')
        ->approved_by->toBe($user->id);
    Storage::disk('public')->assertMissing('expenses/receipts/old.png');
    Storage::disk('public')->assertExists($expense->receipt_path);
});

test('updating requires a valid status', function () {
    $user = userWithPermissions(['edit-expenses']);
    $expense = Expense::factory()->create();

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), ['status' => 'paid'])
        ->assertSessionHasErrors(['status', 'category', 'amount', 'expense_date', 'description']);
});

test('a receipt can be downloaded and deleted', function () {
    Storage::fake('public');
    Storage::disk('public')->put('expenses/receipts/r.png', 'receipt-bytes');
    $user = userWithPermissions(['view-expenses', 'edit-expenses']);
    $expense = Expense::factory()->create(['receipt_path' => 'expenses/receipts/r.png']);

    $this->actingAs($user)
        ->get(route('expenses.receipt.download', $expense))
        ->assertOk()
        ->assertDownload('r.png');

    $this->actingAs($user)
        ->delete(route('expenses.receipt.destroy', $expense))
        ->assertSessionHas('success', 'Receipt deleted successfully');

    expect($expense->fresh()->receipt_path)->toBeNull();
    Storage::disk('public')->assertMissing('expenses/receipts/r.png');

    $this->actingAs($user)
        ->from(route('expenses.show', $expense))
        ->get(route('expenses.receipt.download', $expense))
        ->assertRedirect(route('expenses.show', $expense))
        ->assertSessionHas('error', 'Receipt file not found');
});

test('an expense can be deleted', function () {
    $user = userWithPermissions(['view-expenses', 'delete-expenses']);
    $a = Expense::factory()->create();
    $b = Expense::factory()->create();

    $this->actingAs($user)
        ->from(route('expenses.index'))
        ->delete(route('expenses.destroy', $a))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success', 'Expense deleted successfully');

    $this->actingAs($user)
        ->from(route('expenses.show', $b))
        ->delete(route('expenses.destroy', $b), ['redirect' => 'index'])
        ->assertRedirect(route('expenses.index'));

    expect(Expense::count())->toBe(0);
    expect(Expense::withTrashed()->count())->toBe(2);
});

test('drivers cannot open another user\'s expense or receipt', function () {
    \Spatie\Permission\Models\Role::findOrCreate('driver', 'web');
    $driver = userWithPermissions(['view-expenses']);
    $driver->assignRole('driver');
    $other = App\Models\Expense::factory()->create();
    $own = App\Models\Expense::factory()->create(['user_id' => $driver->id]);

    $this->actingAs($driver)->get(route('expenses.show', $other))->assertForbidden();
    $this->actingAs($driver)->get(route('expenses.receipt.download', $other))->assertForbidden();
    $this->actingAs($driver)->get(route('expenses.show', $own))->assertOk();
});
