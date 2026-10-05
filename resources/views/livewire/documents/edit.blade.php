<?php

use App\Models\Document;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public Document $document;

    #[Validate('required|string|max:255')]
    public $title;

    #[Validate('required|in:contract,license,insurance,receipt,invoice,quote,other')]
    public $category;

    #[Validate('nullable|file|max:10240')]
    public $file_upload = null;

    #[Validate('nullable|exists:clients,id')]
    public $client_id;

    #[Validate('nullable|exists:vehicles,id')]
    public $vehicle_id;

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id;

    #[Validate('nullable|date')]
    public $expiry_date;

    #[Validate('nullable|string|max:1000')]
    public $description;

    #[Validate('nullable|string')]
    public $notes;

    public $createNewVersion = false;

    public function mount(Document $document)
    {
        $this->document = $document;

        $this->title = $this->document->title;
        $this->category = $this->document->category;
        $this->client_id = $this->document->client_id;
        $this->vehicle_id = $this->document->vehicle_id;
        $this->booking_id = $this->document->booking_id;
        $this->expiry_date = $this->document->expiry_date?->format('Y-m-d');
        $this->description = $this->document->description;
        $this->notes = $this->document->notes;
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get(),
            'bookings' => Booking::with(['client', 'vehicle'])
                ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'category' => $this->category,
            'client_id' => $this->client_id ?: null,
            'vehicle_id' => $this->vehicle_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'expiry_date' => $this->expiry_date ?: null,
            'description' => $this->description,
            'notes' => $this->notes,
        ];

        // Handle file replacement
        if ($this->file_upload) {
            if ($this->createNewVersion) {
                // Create new version
                $filePath = $this->file_upload->store('documents', 'public');

                Document::create([
                    ...$data,
                    'file_path' => $filePath,
                    'file_name' => $this->file_upload->getClientOriginalName(),
                    'file_type' => $this->file_upload->getMimeType(),
                    'file_size' => $this->file_upload->getSize(),
                    'uploaded_by' => auth()->id(),
                    'version' => $this->document->version + 1,
                    'parent_document_id' => $this->document->parent_document_id ?: $this->document->id,
                ]);

                $this->dispatch('notify',
                    type: 'success',
                    message: 'New document version created successfully!'
                );
            } else {
                // Replace file
                if (Storage::disk('public')->exists($this->document->file_path)) {
                    Storage::disk('public')->delete($this->document->file_path);
                }

                $filePath = $this->file_upload->store('documents', 'public');

                $data['file_path'] = $filePath;
                $data['file_name'] = $this->file_upload->getClientOriginalName();
                $data['file_type'] = $this->file_upload->getMimeType();
                $data['file_size'] = $this->file_upload->getSize();

                $this->document->update($data);

                $this->dispatch('notify',
                    type: 'success',
                    message: 'Document updated successfully!'
                );
            }
        } else {
            $this->document->update($data);

            $this->dispatch('notify',
                type: 'success',
                message: 'Document updated successfully!'
            );
        }

        return redirect()->route('documents.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Document</flux:heading>

        <div class="flex items-center gap-2">
            @if($document->isExpired())
                <flux:badge color="red">Expired</flux:badge>
            @elseif($document->isExpiringSoon())
                <flux:badge color="yellow">Expiring Soon</flux:badge>
            @endif

            @if($document->version > 1)
                <flux:badge color="blue">v{{ $document->version }}</flux:badge>
            @endif
        </div>
    </flux:header>

    {{-- Current File Info --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">Current File</flux:heading>

        <div class="mt-6 flex items-start gap-4 p-4 bg-gray-50 rounded border border-gray-200">
            <svg class="w-12 h-12 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
            </svg>
            <div class="flex-1">
                <p class="font-medium text-gray-900">{{ $document->file_name }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ $document->file_size_formatted }} • {{ $document->file_type }}</p>
                <p class="text-xs text-gray-500 mt-2">
                    Uploaded by {{ $document->uploadedBy->name }} on {{ $document->created_at->format('d M Y, H:i') }}
                </p>
            </div>
            <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </flux:card>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Document Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Document Title *</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Category *</flux:label>
                    <flux:select wire:model="category">
                        <option value="">Select category</option>
                        <option value="contract">Contract</option>
                        <option value="license">License</option>
                        <option value="insurance">Insurance</option>
                        <option value="receipt">Receipt</option>
                        <option value="invoice">Invoice</option>
                        <option value="quote">Quote</option>
                        <option value="other">Other</option>
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                <flux:field>
                    <flux:label>Expiry Date</flux:label>
                    <flux:input wire:model="expiry_date" type="date" />
                    <flux:error name="expiry_date" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                    <flux:error name="description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Replace File (Optional)</flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>Upload New File</flux:label>
                    <flux:input wire:model="file_upload" type="file" />
                    <flux:error name="file_upload" />
                    <flux:description>Upload a new file to replace the current one</flux:description>
                </flux:field>

                @if($file_upload)
                    <div class="p-4 bg-blue-50 rounded border border-blue-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-blue-900">{{ $file_upload->getClientOriginalName() }}</p>
                                <p class="text-xs text-blue-700">
                                    {{ number_format($file_upload->getSize() / 1024, 2) }} KB
                                </p>
                            </div>
                        </div>
                    </div>

                    <flux:field>
                        <flux:checkbox wire:model="createNewVersion">
                            Create new version (keep old file)
                        </flux:checkbox>
                        <flux:description>
                            If checked, a new version will be created. Otherwise, the current file will be replaced.
                        </flux:description>
                    </flux:field>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Link to Records</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label>Client</flux:label>
                    <flux:select wire:model="client_id">
                        <option value="">None</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }}
                                @if($client->company_name)
                                    ({{ $client->company_name }})
                                @endif
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <flux:select wire:model="vehicle_id">
                        <option value="">None</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Booking</flux:label>
                    <flux:select wire:model="booking_id">
                        <option value="">None</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">
                                {{ $booking->booking_number }} - {{ $booking->client->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="booking_id" />
                </flux:field>
            </div>
        </flux:card>

        @if($document->versions->count() > 0)
            <flux:card>
                <flux:heading size="lg">Version History</flux:heading>

                <div class="mt-6">
                    <flux:table>
                        <flux:columns>
                            <flux:column>Version</flux:column>
                            <flux:column>File Name</flux:column>
                            <flux:column>Uploaded</flux:column>
                            <flux:column>Size</flux:column>
                            <flux:column>Actions</flux:column>
                        </flux:columns>

                        <flux:rows>
                            @foreach($document->versions->sortByDesc('version') as $version)
                                <flux:row :key="$version->id">
                                    <flux:cell>v{{ $version->version }}</flux:cell>
                                    <flux:cell>{{ $version->file_name }}</flux:cell>
                                    <flux:cell>
                                        {{ $version->created_at->format('d M Y, H:i') }}
                                        <div class="text-xs text-gray-500">by {{ $version->uploadedBy->name }}</div>
                                    </flux:cell>
                                    <flux:cell>{{ $version->file_size_formatted }}</flux:cell>
                                    <flux:cell>
                                        <a href="{{ Storage::url($version->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm">
                                            Download
                                        </a>
                                    </flux:cell>
                                </flux:row>
                            @endforeach
                        </flux:rows>
                    </flux:table>
                </div>
            </flux:card>
        @endif

        <flux:card>
            <flux:heading size="lg">Internal Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Document
            </flux:button>

            <flux:button wire:navigate href="{{ route('documents.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
