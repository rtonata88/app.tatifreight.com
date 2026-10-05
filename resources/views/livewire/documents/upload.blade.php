<?php

use App\Models\Document;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|in:contract,license,insurance,receipt,invoice,quote,other')]
    public $category = '';

    #[Validate('required|file|max:10240')]
    public $file_upload = null;

    #[Validate('nullable|exists:clients,id')]
    public $client_id = '';

    #[Validate('nullable|exists:vehicles,id')]
    public $vehicle_id = '';

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id = '';

    #[Validate('nullable|date')]
    public $expiry_date = '';

    #[Validate('nullable|string|max:1000')]
    public $description = '';

    #[Validate('nullable|string')]
    public $notes = '';

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

        if (!$this->file_upload) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Please select a file to upload'
            );
            return;
        }

        // Store file
        $filePath = $this->file_upload->store('documents', 'public');
        $fileName = $this->file_upload->getClientOriginalName();
        $fileType = $this->file_upload->getMimeType();
        $fileSize = $this->file_upload->getSize();

        // Create document record
        Document::create([
            'title' => $this->title,
            'category' => $this->category,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'uploaded_by' => auth()->id(),
            'client_id' => $this->client_id ?: null,
            'vehicle_id' => $this->vehicle_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'expiry_date' => $this->expiry_date ?: null,
            'description' => $this->description,
            'notes' => $this->notes,
            'version' => 1,
        ]);

        $this->dispatch('notify',
            type: 'success',
            message: 'Document uploaded successfully!'
        );

        return redirect()->route('documents.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Upload Document</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Document Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Document Title *</flux:label>
                    <flux:input wire:model="title" placeholder="e.g., Vehicle License Disc - ABC123" />
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
                    <flux:description>Leave blank if document doesn't expire</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Brief description of the document..." />
                    <flux:error name="description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">File Upload</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Select File *</flux:label>
                    <flux:input wire:model="file_upload" type="file" />
                    <flux:error name="file_upload" />
                    <flux:description>Maximum file size: 10MB. Supported formats: PDF, Images, Word, Excel</flux:description>
                </flux:field>

                @if ($file_upload)
                    <div class="mt-4 p-4 bg-gray-50 rounded border border-gray-200">
                        <p class="text-sm font-medium text-gray-700">File Preview:</p>
                        <div class="mt-2 flex items-center gap-3">
                            <svg class="w-10 h-10 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium">{{ $file_upload->getClientOriginalName() }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ number_format($file_upload->getSize() / 1024, 2) }} KB
                                </p>
                            </div>
                        </div>

                        @if(str_contains($file_upload->getMimeType(), 'image'))
                            <div class="mt-3">
                                <img src="{{ $file_upload->temporaryUrl() }}" class="max-w-md rounded border border-gray-200">
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Link to Records (Optional)</flux:heading>

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

        <flux:card>
            <flux:heading size="lg">Internal Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Internal notes (not visible in document metadata)..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Upload Document
            </flux:button>

            <flux:button wire:navigate href="{{ route('documents.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
