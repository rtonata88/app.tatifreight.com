<?php

use App\Models\Document;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $expiryFilter = '';

    public function with(): array
    {
        $query = Document::with(['uploadedBy', 'client', 'vehicle', 'booking'])
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('file_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->categoryFilter, function ($q) {
                $q->where('category', $this->categoryFilter);
            })
            ->when($this->expiryFilter, function ($q) {
                if ($this->expiryFilter === 'expired') {
                    $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
                } elseif ($this->expiryFilter === 'expiring_soon') {
                    $q->whereNotNull('expiry_date')
                      ->whereDate('expiry_date', '>=', now())
                      ->whereDate('expiry_date', '<=', now()->addDays(30));
                }
            })
            ->orderBy('created_at', 'desc');

        $expiredCount = Document::whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count();
        $expiringSoonCount = Document::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();

        return [
            'documents' => $query->paginate(15),
            'stats' => [
                'total' => Document::count(),
                'expired' => $expiredCount,
                'expiring_soon' => $expiringSoonCount,
                'by_category' => [
                    'contracts' => Document::where('category', 'contract')->count(),
                    'licenses' => Document::where('category', 'license')->count(),
                    'insurance' => Document::where('category', 'insurance')->count(),
                    'receipts' => Document::where('category', 'receipt')->count(),
                ],
            ],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingExpiryFilter(): void
    {
        $this->resetPage();
    }

    public function downloadDocument(int $id): void
    {
        $document = Document::findOrFail($id);

        if (Storage::disk('public')->exists($document->file_path)) {
            return response()->download(
                Storage::disk('public')->path($document->file_path),
                $document->file_name
            );
        }

        $this->dispatch('notify',
            type: 'error',
            message: 'File not found'
        );
    }

    public function deleteDocument(int $id): void
    {
        if (auth()->user()->can('delete-documents')) {
            $document = Document::findOrFail($id);

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
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
        <flux:heading size="xl">Document Management</flux:heading>

        @can('create-documents')
            <flux:button wire:navigate href="{{ route('documents.upload') }}" icon="plus">
                Upload Document
            </flux:button>
        @endcan
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Documents</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $stats['total'] }}</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-red-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-red-700">{{ $stats['expired'] }}</p>
                </div>
                <div class="text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['expiring_soon'] }}</p>
                </div>
                <div class="text-yellow-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Contracts</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['by_category']['contracts'] }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search documents..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="categoryFilter" placeholder="Filter by category">
                    <option value="">All Categories</option>
                    <option value="contract">Contracts</option>
                    <option value="license">Licenses</option>
                    <option value="insurance">Insurance</option>
                    <option value="receipt">Receipts</option>
                    <option value="invoice">Invoices</option>
                    <option value="quote">Quotes</option>
                    <option value="other">Other</option>
                </flux:select>

                <flux:select wire:model.live="expiryFilter" placeholder="Filter by expiry">
                    <option value="">All Documents</option>
                    <option value="expired">Expired</option>
                    <option value="expiring_soon">Expiring Soon (30 days)</option>
                </flux:select>
            </div>

            {{-- Documents Table --}}
            <flux:table>
                <flux:columns>
                    <flux:column>Document</flux:column>
                    <flux:column>Category</flux:column>
                    <flux:column>Related To</flux:column>
                    <flux:column>Uploaded By</flux:column>
                    <flux:column>Expiry Date</flux:column>
                    <flux:column>Size</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($documents as $document)
                        <flux:row :key="$document->id">
                            <flux:cell>
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-1">
                                        @if(str_contains($document->file_type, 'pdf'))
                                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 18h12V6h-4V2H4v16zm-2 1V0h12l4 4v16H2v-1z"/>
                                            </svg>
                                        @elseif(str_contains($document->file_type, 'image'))
                                            <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                            </svg>
                                        @else
                                            <svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ $document->title }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $document->file_name }}</p>
                                        @if($document->description)
                                            <p class="text-xs text-gray-400 mt-1">{{ Str::limit($document->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <flux:badge color="gray" size="sm">
                                    {{ ucfirst($document->category) }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    @if($document->client)
                                        <div class="font-medium">{{ $document->client->name }}</div>
                                        <div class="text-xs text-gray-500">Client</div>
                                    @elseif($document->vehicle)
                                        <div class="font-medium">{{ $document->vehicle->reg_number }}</div>
                                        <div class="text-xs text-gray-500">Vehicle</div>
                                    @elseif($document->booking)
                                        <div class="font-medium">{{ $document->booking->booking_number }}</div>
                                        <div class="text-xs text-gray-500">Booking</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    <div>{{ $document->uploadedBy->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $document->created_at->format('d M Y') }}</div>
                                </div>
                            </flux:cell>

                            <flux:cell>
                                @if($document->expiry_date)
                                    <div class="text-sm">
                                        <div class="{{ $document->isExpired() ? 'text-red-600 font-medium' : ($document->isExpiringSoon() ? 'text-yellow-600' : '') }}">
                                            {{ $document->expiry_date->format('d M Y') }}
                                        </div>
                                        @if($document->isExpired())
                                            <div class="text-xs text-red-600">Expired</div>
                                        @elseif($document->isExpiringSoon())
                                            <div class="text-xs text-yellow-600">
                                                {{ $document->expiry_date->diffInDays(now()) }} days left
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm text-gray-600">{{ $document->file_size_formatted }}</div>
                            </flux:cell>

                            <flux:cell>
                                <div class="flex gap-2">
                                    <flux:button
                                        wire:click="downloadDocument({{ $document->id }})"
                                        size="sm"
                                        variant="ghost"
                                        icon="arrow-down-tray"
                                    >
                                        Download
                                    </flux:button>

                                    @can('edit-documents')
                                        <flux:button
                                            wire:navigate
                                            href="{{ route('documents.edit', $document) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                        >
                                            Edit
                                        </flux:button>
                                    @endcan

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
                            <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                No documents found. Upload your first document to get started.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        </div>
    </flux:card>
</div>
