<?php

use App\Models\Client;
use App\Models\Document;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads, WithPagination;

    public Client $client;
    public $showUploadModal = false;
    public $uploadFile;
    public $uploadTitle;
    public $uploadCategory;
    public $uploadDescription;

    public function mount(Client $client)
    {
        $this->client = $client;
    }

    public function with(): array
    {
        $documents = Document::where('documentable_type', Client::class)
            ->where('documentable_id', $this->client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return [
            'documents' => $documents,
        ];
    }

    public function openUploadModal()
    {
        $this->showUploadModal = true;
        $this->reset(['uploadFile', 'uploadTitle', 'uploadCategory', 'uploadDescription']);
    }

    public function closeUploadModal()
    {
        $this->showUploadModal = false;
        $this->reset(['uploadFile', 'uploadTitle', 'uploadCategory', 'uploadDescription']);
    }

    public function uploadDocument()
    {
        $this->validate([
            'uploadFile' => 'required|file|max:10240', // 10MB max
            'uploadTitle' => 'required|string|max:255',
            'uploadCategory' => 'required|string',
            'uploadDescription' => 'nullable|string',
        ]);

        try {
            $path = $this->uploadFile->store('documents/clients/' . $this->client->id, 'local');

            Document::create([
                'documentable_type' => Client::class,
                'documentable_id' => $this->client->id,
                'title' => $this->uploadTitle,
                'category' => $this->uploadCategory,
                'description' => $this->uploadDescription,
                'file_path' => $path,
                'file_name' => $this->uploadFile->getClientOriginalName(),
                'file_type' => $this->uploadFile->extension(),
                'file_size' => $this->uploadFile->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Document uploaded successfully'
            );

            $this->closeUploadModal();
        } catch (\Exception $e) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Failed to upload document: ' . $e->getMessage()
            );
        }
    }

    public function deleteDocument($id)
    {
        if (auth()->user()->can('delete-documents')) {
            $document = Document::findOrFail($id);
            
            // Delete file from storage
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Document deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <div>
            <flux:heading size="xl">Client Documents</flux:heading>
            <flux:subheading>{{ $client->company_name ?: $client->name }}</flux:subheading>
        </div>

        <div class="flex gap-3">
            <flux:button wire:navigate href="{{ route('clients.index') }}" variant="ghost" icon="arrow-left">
                Back to Clients
            </flux:button>
            @can('create-documents')
                <flux:button wire:click="openUploadModal" icon="arrow-up-tray" variant="primary">
                    Upload Document
                </flux:button>
            @endcan
        </div>
    </flux:header>

    {{-- Client Information --}}
    <flux:card class="mt-6 bg-blue-50">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="font-semibold text-gray-900">{{ $client->company_name ?: $client->name }}</div>
                <div class="text-sm text-gray-600">{{ $client->email }} @if($client->phone) • {{ $client->phone }}@endif</div>
            </div>
            <flux:badge :color="$client->is_active ? 'green' : 'red'">
                {{ $client->is_active ? 'Active' : 'Inactive' }}
            </flux:badge>
        </div>
    </flux:card>

    {{-- Documents Table --}}
    <flux:card class="mt-6">
        <flux:table>
            <flux:columns>
                <flux:column>Document</flux:column>
                <flux:column>Category</flux:column>
                <flux:column>File Info</flux:column>
                <flux:column>Uploaded</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($documents as $document)
                    <flux:row :key="$document->id">
                        <flux:cell>
                            <div>
                                <div class="font-medium text-gray-900">{{ $document->title }}</div>
                                @if($document->description)
                                    <div class="text-sm text-gray-500">{{ $document->description }}</div>
                                @endif
                            </div>
                        </flux:cell>

                        <flux:cell>
                            <flux:badge
                                :color="match($document->category) {
                                    'contract' => 'blue',
                                    'invoice' => 'green',
                                    'quote' => 'yellow',
                                    'compliance' => 'purple',
                                    default => 'gray'
                                }"
                                size="sm"
                            >
                                {{ ucfirst($document->category) }}
                            </flux:badge>
                        </flux:cell>

                        <flux:cell>
                            <div class="text-sm">
                                <div class="font-medium">{{ $document->file_name }}</div>
                                <div class="text-gray-500">
                                    {{ strtoupper($document->file_type) }} • {{ number_format($document->file_size / 1024, 1) }} KB
                                </div>
                            </div>
                        </flux:cell>

                        <flux:cell>
                            <div class="text-sm">
                                <div>{{ $document->created_at->format('d M Y') }}</div>
                                <div class="text-gray-500">by {{ $document->uploader->name ?? 'Unknown' }}</div>
                            </div>
                        </flux:cell>

                        <flux:cell>
                            <div class="flex gap-2">
                                <flux:button
                                    href="{{ route('documents.download', $document) }}"
                                    size="sm"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                >
                                    Download
                                </flux:button>

                                @can('delete-documents')
                                    <flux:button
                                        wire:click="deleteDocument({{ $document->id }})"
                                        wire:confirm="Are you sure you want to delete this document?"
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                    >
                                        Delete
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="5" class="text-center text-gray-500 py-8">
                            No documents uploaded yet. Click "Upload Document" to add documents for this client.
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    </flux:card>

    {{-- Upload Modal --}}
    @if($showUploadModal)
        <flux:modal name="upload-document" class="min-w-[600px]" wire:model="showUploadModal">
            <div>
                <flux:heading size="lg" class="mb-6">Upload Document</flux:heading>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Document Title *</flux:label>
                        <flux:input wire:model="uploadTitle" placeholder="e.g., Service Agreement 2024" />
                        @error('uploadTitle') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Category *</flux:label>
                        <flux:select wire:model="uploadCategory" placeholder="Select category">
                            <option value="contract">Contract</option>
                            <option value="invoice">Invoice</option>
                            <option value="quote">Quote</option>
                            <option value="compliance">Compliance</option>
                            <option value="correspondence">Correspondence</option>
                            <option value="other">Other</option>
                        </flux:select>
                        @error('uploadCategory') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="uploadDescription" placeholder="Optional description" rows="3" />
                        @error('uploadDescription') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>File * (Max 10MB)</flux:label>
                        <input type="file" wire:model="uploadFile" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100
                        "/>
                        @error('uploadFile') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    @if($uploadFile)
                        <div class="text-sm text-gray-600">
                            Selected: {{ $uploadFile->getClientOriginalName() }}
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 mt-6 justify-end">
                    <flux:button variant="ghost" wire:click="closeUploadModal">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="uploadDocument">Upload Document</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

