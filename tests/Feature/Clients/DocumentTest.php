<?php

use App\Models\Client;
use App\Models\Document;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see documents', function () {
    $this->get(route('documents.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $document = Document::factory()->create();

    $this->actingAs($user)->get(route('documents.index'))->assertForbidden();
    $this->actingAs($user)->get(route('documents.upload'))->assertForbidden();
    $this->actingAs($user)->get(route('documents.edit', $document))->assertForbidden();
    $this->actingAs($user)->delete(route('documents.destroy', $document))->assertForbidden();
});

test('documents index shows stats and filters by category and expiry', function () {
    $user = userWithPermissions(['view-documents', 'edit-documents']);
    $client = Client::factory()->create(['name' => 'Namib Logistics']);
    Document::factory()->create(['title' => 'Old licence', 'category' => 'license', 'expiry_date' => now()->subDays(5)]);
    Document::factory()->create(['title' => 'Soon insurance', 'category' => 'insurance', 'expiry_date' => now()->addDays(10)]);
    Document::factory()->create(['title' => 'Agreement', 'category' => 'contract', 'client_id' => $client->id]);

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->has('documents.data', 3)
            ->where('stats.total', 3)
            ->where('stats.expired', 1)
            ->where('stats.expiring_soon', 1)
            ->where('stats.by_category.contracts', 1)
            ->where('stats.by_category.licenses', 1)
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('documents.index', ['expiry' => 'expiring_soon']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1)
            ->where('documents.data.0.title', 'Soon insurance')
            ->where('documents.data.0.is_expiring_soon', true)
            ->where('documents.data.0.days_left', 10)
        );

    $this->actingAs($user)
        ->get(route('documents.index', ['expiry' => 'expired']))
        ->assertInertia(fn (Assert $page) => $page->has('documents.data', 1)->where('documents.data.0.is_expired', true));

    // Search matches the linked client's name, and combines with the category filter.
    $this->actingAs($user)
        ->get(route('documents.index', ['search' => 'Namib', 'category' => 'contract']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1)
            ->where('documents.data.0.related.label', 'Namib Logistics')
            ->where('documents.data.0.related.type', 'Client')
            ->where('filters.search', 'Namib')
        );
});

test('a document can be uploaded to the private disk', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = userWithPermissions(['view-documents', 'create-documents']);
    $client = Client::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.upload'))
        ->assertInertia(fn (Assert $page) => $page->component('documents/upload')->has('clients', 1)->has('vehicles', 1)->has('bookings', 0));

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'License Disc',
            'category' => 'license',
            'file_upload' => UploadedFile::fake()->create('disc.pdf', 120, 'application/pdf'),
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'booking_id' => '',
            'expiry_date' => '2027-01-31',
        ])
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success', 'Document uploaded successfully!');

    $document = Document::where('title', 'License Disc')->firstOrFail();
    expect($document)
        ->file_name->toBe('disc.pdf')
        ->file_type->toBe('application/pdf')
        ->uploaded_by->toBe($user->id)
        ->client_id->toBe($client->id)
        ->booking_id->toBeNull()
        ->version->toBe(1)
        ->and($document->expiry_date->format('Y-m-d'))->toBe('2027-01-31');
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
});

test('uploading validates required fields and category', function () {
    $user = userWithPermissions(['create-documents']);

    $this->actingAs($user)
        ->post(route('documents.store'), ['category' => 'compliance', 'client_id' => 999])
        ->assertSessionHasErrors(['title', 'category', 'file_upload', 'client_id']);
});

test('a document can be edited and its file replaced', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = userWithPermissions(['view-documents', 'edit-documents']);
    Storage::disk('public')->put('documents/old.pdf', 'old');
    $document = Document::factory()->create(['file_path' => 'documents/old.pdf', 'category' => 'contract']);

    $this->actingAs($user)
        ->get(route('documents.edit', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/edit')
            ->where('document.id', $document->id)
            ->where('document.category', 'contract')
            ->has('versions', 0)
        );

    $this->actingAs($user)
        ->post(route('documents.update', $document), [
            '_method' => 'put',
            'title' => 'Renamed',
            'category' => 'quote',
            'file_upload' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'),
            'createNewVersion' => '0',
        ])
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success', 'Document updated successfully!');

    $document->refresh();
    expect($document)->title->toBe('Renamed')->category->toBe('quote')->file_name->toBe('new.pdf');
    Storage::disk('public')->assertMissing('documents/old.pdf');
    Storage::disk('local')->assertExists($document->file_path);
    expect(Document::count())->toBe(1);
});

test('editing with create new version keeps the old file', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = userWithPermissions(['view-documents', 'edit-documents']);
    Storage::disk('public')->put('documents/v1.pdf', 'v1');
    $document = Document::factory()->create(['file_path' => 'documents/v1.pdf', 'category' => 'contract', 'title' => 'Agreement']);

    $this->actingAs($user)
        ->post(route('documents.update', $document), [
            '_method' => 'put',
            'title' => 'Agreement',
            'category' => 'contract',
            'file_upload' => UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'),
            'createNewVersion' => '1',
        ])
        ->assertSessionHas('success', 'New document version created successfully!');

    $version = Document::where('parent_document_id', $document->id)->firstOrFail();
    expect($version)->version->toBe(2)->file_name->toBe('v2.pdf')->uploaded_by->toBe($user->id);
    Storage::disk('public')->assertExists('documents/v1.pdf');
    Storage::disk('local')->assertExists($version->file_path);
    expect($document->fresh()->file_path)->toBe('documents/v1.pdf');

    $this->actingAs($user)
        ->get(route('documents.edit', $document))
        ->assertInertia(fn (Assert $page) => $page->has('versions', 1)->where('versions.0.version', 2));
});

test('a legacy public document can be deleted with its file', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = userWithPermissions(['view-documents', 'delete-documents']);
    Storage::disk('public')->put('documents/gone.pdf', 'x');
    $document = Document::factory()->create(['file_path' => 'documents/gone.pdf']);

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertSessionHas('success', 'Document deleted successfully');

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('public')->assertMissing('documents/gone.pdf');
});

test('a private document can be deleted with its file', function () {
    Storage::fake('local');
    $user = userWithPermissions(['view-documents', 'delete-documents']);
    Storage::disk('local')->put('documents/private.pdf', 'x');
    $document = Document::factory()->create(['file_path' => 'documents/private.pdf']);

    $this->actingAs($user)->delete(route('documents.destroy', $document))->assertSessionHas('success');

    Storage::disk('local')->assertMissing('documents/private.pdf');
});

test('private library documents download only through the permission-checked route', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('documents/secret.pdf', 'content');
    $document = Document::factory()->create(['file_path' => 'documents/secret.pdf', 'file_name' => 'Secret.pdf', 'category' => 'contract']);

    $this->get(route('documents.file', $document))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions([]))->get(route('documents.file', $document))->assertForbidden();
    $this->actingAs(userWithPermissions(['view-documents']))->get(route('documents.file', $document))->assertDownload('Secret.pdf');

    $this->actingAs(userWithPermissions(['view-documents', 'edit-documents']))
        ->get(route('documents.edit', $document))
        ->assertInertia(fn (Assert $page) => $page->where('document.file_url', route('documents.file', $document)));
});

test('uploads that could run as code or a page are refused', function (string $name, string $mime) {
    Storage::fake('local');
    $user = userWithPermissions(['view-documents', 'create-documents']);
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), ['title' => 'x', 'category' => 'other', 'file_upload' => UploadedFile::fake()->create($name, 1, $mime)])
        ->assertSessionHasErrors('file_upload');

    $this->actingAs($user)
        ->post(route('clients.documents.store', $client), ['uploadTitle' => 'x', 'uploadCategory' => 'other', 'uploadFile' => UploadedFile::fake()->create($name, 1, $mime)])
        ->assertSessionHasErrors('uploadFile');

    expect(Document::count())->toBe(0);
})->with([
    'php' => ['shell.php', 'application/x-php'],
    'html' => ['page.html', 'text/html'],
    'svg' => ['image.svg', 'image/svg+xml'],
]);

test('legacy library documents still download from the public disk', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = userWithPermissions(['view-documents']);
    Storage::disk('public')->put('documents/here.pdf', 'content');
    $here = Document::factory()->create(['file_path' => 'documents/here.pdf', 'file_name' => 'Here.pdf']);
    $missing = Document::factory()->create(['file_path' => 'documents/missing.pdf']);

    $this->actingAs($user)->get(route('documents.file', $here))->assertOk()->assertDownload('Here.pdf');
    $this->actingAs($user)->from(route('documents.index'))->get(route('documents.file', $missing))
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('error', 'File not found');
});

test('client documents page lists only that client\'s documents', function () {
    $user = userWithPermissions(['view-documents', 'create-documents']);
    $client = Client::factory()->create();
    $other = Client::factory()->create();
    Document::factory()->create(['documentable_type' => Client::class, 'documentable_id' => $client->id, 'title' => 'Mine', 'file_type' => 'pdf', 'file_size' => 2048]);
    Document::factory()->create(['documentable_type' => Client::class, 'documentable_id' => $other->id, 'title' => 'Theirs']);

    $this->actingAs($user)
        ->get(route('clients.documents', $client))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/documents')
            ->where('client.id', $client->id)
            ->has('documents.data', 1)
            ->where('documents.data.0.title', 'Mine')
            ->where('documents.data.0.file_size_kb', 2)
            ->where('can.create', true)
            ->where('can.delete', false)
        );

    $this->actingAs(userWithPermissions(['view-clients']))
        ->get(route('clients.documents', $client))
        ->assertForbidden();
});

test('a client document can be uploaded, downloaded and deleted', function () {
    Storage::fake('local');
    $user = userWithPermissions(['view-documents', 'create-documents', 'delete-documents']);
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->from(route('clients.documents', $client))
        ->post(route('clients.documents.store', $client), [
            'uploadTitle' => 'Service Agreement 2024',
            'uploadCategory' => 'compliance',
            'uploadDescription' => 'Signed copy',
            'uploadFile' => UploadedFile::fake()->create('agreement.pdf', 50, 'application/pdf'),
        ])
        ->assertRedirect(route('clients.documents', $client))
        ->assertSessionHas('success', 'Document uploaded successfully');

    $document = Document::where('title', 'Service Agreement 2024')->firstOrFail();
    expect($document)
        ->documentable_type->toBe(Client::class)
        ->documentable_id->toBe($client->id)
        ->category->toBe('compliance')
        ->file_type->toBe('pdf')
        ->and($document->file_path)->toStartWith("documents/clients/{$client->id}/");
    Storage::disk('local')->assertExists($document->file_path);

    $this->actingAs($user)->get(route('documents.download', $document))->assertOk()->assertDownload('agreement.pdf');

    $this->actingAs($user)
        ->delete(route('clients.documents.destroy', ['client' => $client, 'document' => $document]))
        ->assertSessionHas('success', 'Document deleted successfully');

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($document->file_path);
});

test('client document upload validates input', function () {
    $user = userWithPermissions(['view-documents', 'create-documents']);
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('clients.documents.store', $client), [])
        ->assertSessionHasErrors(['uploadFile', 'uploadTitle', 'uploadCategory']);
});

test('client document download 404s when the file is missing', function () {
    Storage::fake('local');
    $document = Document::factory()->create(['file_path' => 'documents/clients/1/none.pdf']);

    $this->actingAs(userWithPermissions(['view-documents']))
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

test('seeded roles can reach documents according to their role', function () {
    $this->seed(Database\Seeders\RolesAndPermissionsSeeder::class);

    $userWithRole = fn (string $role) => tap(App\Models\User::factory()->create())->assignRole($role);

    $this->actingAs($userWithRole('admin'))->get(route('documents.index'))->assertOk();
    $this->actingAs($userWithRole('manager'))->get(route('documents.upload'))->assertOk();
    $this->actingAs($userWithRole('accountant'))->get(route('documents.index'))->assertOk();
    $this->actingAs($userWithRole('accountant'))->get(route('documents.upload'))->assertForbidden();
    $this->actingAs($userWithRole('driver'))->get(route('documents.index'))->assertForbidden();
});
