<?php

use App\Models\Quote;
use App\Models\Booking;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $query = Quote::with(['client', 'createdBy', 'booking'])
            ->when($this->search, function ($q) {
                $q->where('quote_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                  ->orWhere('company_name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');

        return [
            'quotes' => $query->paginate(10),
            'stats' => [
                'draft' => Quote::where('status', 'draft')->count(),
                'sent' => Quote::where('status', 'sent')->count(),
                'approved' => Quote::where('status', 'approved')->count(),
                'rejected' => Quote::where('status', 'rejected')->count(),
                'expired' => Quote::where('status', 'expired')->count(),
            ],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function markAsSent(int $id): void
    {
        if (auth()->user()->can('edit-quotes')) {
            $quote = Quote::findOrFail($id);

            $quote->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote marked as sent'
            );
        }
    }

    public function markAsApproved(int $id): void
    {
        if (auth()->user()->can('edit-quotes')) {
            $quote = Quote::findOrFail($id);

            $quote->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote approved'
            );
        }
    }

    public function markAsRejected(int $id): void
    {
        if (auth()->user()->can('edit-quotes')) {
            $quote = Quote::findOrFail($id);

            $quote->update([
                'status' => 'rejected',
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote rejected'
            );
        }
    }

    public function markAsExpired(int $id): void
    {
        if (auth()->user()->can('edit-quotes')) {
            $quote = Quote::findOrFail($id);

            $quote->update([
                'status' => 'expired',
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote marked as expired'
            );
        }
    }

    public function duplicateQuote(int $id): void
    {
        if (auth()->user()->can('create-quotes')) {
            $originalQuote = Quote::with('lineItems')->findOrFail($id);

            // Generate new quote number
            $lastQuote = Quote::latest('id')->first();
            $nextNumber = $lastQuote ? (int)substr($lastQuote->quote_number, 3) + 1 : 1;
            $quoteNumber = 'QT-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Create duplicate
            $newQuote = Quote::create([
                'quote_number' => $quoteNumber,
                'client_id' => $originalQuote->client_id,
                'created_by' => auth()->id(),
                'version' => 1,
                'status' => 'draft',
                'valid_until' => now()->addDays(30),
                'description' => $originalQuote->description,
                'terms_conditions' => $originalQuote->terms_conditions,
                'subtotal' => $originalQuote->subtotal,
                'tax_amount' => $originalQuote->tax_amount,
                'total' => $originalQuote->total,
                'notes' => $originalQuote->notes,
            ]);

            // Duplicate line items
            foreach ($originalQuote->lineItems as $item) {
                $newQuote->lineItems()->create([
                    'vehicle_id' => $item->vehicle_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'unit' => $item->unit,
                ]);
            }

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote duplicated successfully'
            );
        }
    }

    public function convertToBooking(int $id): void
    {
        if (!auth()->user()->can('create-bookings')) {
            $this->dispatch('notify',
                type: 'error',
                message: 'You do not have permission to create bookings'
            );
            return;
        }

        $quote = Quote::with('lineItems')->findOrFail($id);

        if ($quote->status !== 'approved') {
            $this->dispatch('notify',
                type: 'error',
                message: 'Only approved quotes can be converted to bookings'
            );
            return;
        }

        if ($quote->booking()->exists()) {
            $this->dispatch('notify',
                type: 'error',
                message: 'This quote has already been converted to a booking'
            );
            return;
        }

        // Get the first line item with a vehicle to determine the vehicle for booking
        $vehicleItem = $quote->lineItems->whereNotNull('vehicle_id')->first();

        if (!$vehicleItem) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Quote must have at least one line item with a vehicle to convert to booking'
            );
            return;
        }

        // Generate booking number
        $lastBooking = Booking::latest('id')->first();
        $nextNumber = $lastBooking ? (int)substr($lastBooking->booking_number, 4) + 1 : 1;
        $bookingNumber = 'BKG-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Create booking
        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'client_id' => $quote->client_id,
            'vehicle_id' => $vehicleItem->vehicle_id,
            'quote_id' => $quote->id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'notes' => $quote->description,
        ]);

        // Note: Vehicle status will be updated when booking status changes to 'in_progress'
        // For immediate bookings (starting now), consider setting status to 'in_progress'

        $this->dispatch('notify',
            type: 'success',
            message: 'Booking created successfully from quote!'
        );
    }

    public function deleteQuote(int $id): void
    {
        if (auth()->user()->can('delete-quotes')) {
            Quote::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Quote deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Quotations</flux:heading>

        @can('create-quotes')
            <flux:button wire:navigate href="{{ route('quotes.create') }}" icon="plus">
                New Quote
            </flux:button>
        @endcan
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        <flux:card class="bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Draft</p>
                    <p class="text-2xl font-bold text-gray-700">{{ $stats['draft'] }}</p>
                </div>
                <div class="text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Sent</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $stats['sent'] }}</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['approved'] }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-red-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-red-700">{{ $stats['rejected'] }}</p>
                </div>
                <div class="text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-orange-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-orange-700">{{ $stats['expired'] }}</p>
                </div>
                <div class="text-orange-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search quotes or clients..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </flux:select>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-4">
                @forelse($quotes as $quote)
                    <flux:card>
                        <div class="space-y-3">
                            {{-- Header with Quote # and Total --}}
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-lg text-gray-900">
                                        {{ $quote->quote_number }}
                                        @if($quote->version > 1)
                                            <span class="text-xs text-gray-500 font-normal">(v{{ $quote->version }})</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600">{{ $quote->createdBy->name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Total</div>
                                    <div class="font-bold text-xl text-blue-700">N${{ number_format($quote->total, 2) }}</div>
                                    <div class="text-xs text-gray-500">excl VAT: N${{ number_format($quote->subtotal, 2) }}</div>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div>
                                <flux:badge
                                    :color="match($quote->status) {
                                        'draft' => 'gray',
                                        'sent' => 'blue',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'expired' => 'orange',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($quote->status) }}
                                </flux:badge>
                            </div>

                            {{-- Client Info --}}
                            <div class="border-t pt-3">
                                <div class="text-sm">
                                    <div class="text-xs text-gray-500 mb-1">Client</div>
                                    <div class="font-medium text-gray-900">{{ $quote->client->name }}</div>
                                    @if($quote->client->company_name)
                                        <div class="text-xs text-gray-500">{{ $quote->client->company_name }}</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 border-t pt-3">
                                <div>
                                    <div class="text-xs text-gray-500">Valid Until</div>
                                    <div class="text-sm font-medium {{ $quote->valid_until && $quote->valid_until->isPast() && $quote->status !== 'approved' ? 'text-red-600' : '' }}">
                                        {{ $quote->valid_until?->format('d M Y') ?? 'N/A' }}
                                    </div>
                                    @if($quote->valid_until && $quote->valid_until->isPast() && $quote->status !== 'approved')
                                        <div class="text-xs text-red-600">Expired</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Created</div>
                                    <div class="text-sm font-medium">{{ $quote->created_at->format('d M Y') }}</div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="border-t pt-3">
                                <div class="w-full">
                                    <flux:dropdown position="right" align="end" class="w-full">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" class="w-full">
                                            Actions
                                        </flux:button>

                                    <flux:menu class="min-w-48">
                                        @can('view-quotes')
                                            <flux:menu.item icon="eye" href="{{ route('quotes.pdf.view', $quote) }}" target="_blank">
                                                View PDF
                                            </flux:menu.item>

                                            <flux:menu.item icon="arrow-down-tray" href="{{ route('quotes.pdf.download', $quote) }}">
                                                Download PDF
                                            </flux:menu.item>

                                            <flux:menu.separator />
                                        @endcan

                                        @can('edit-quotes')
                                            @if($quote->status === 'draft')
                                                <flux:menu.item icon="paper-airplane" wire:click="markAsSent({{ $quote->id }})">
                                                    Mark as Sent
                                                </flux:menu.item>
                                            @endif

                                            @if($quote->status === 'sent')
                                                <flux:menu.item icon="check-circle" wire:click="markAsApproved({{ $quote->id }})">
                                                    Mark as Approved
                                                </flux:menu.item>

                                                <flux:menu.item icon="x-circle" wire:click="markAsRejected({{ $quote->id }})">
                                                    Mark as Rejected
                                                </flux:menu.item>
                                            @endif

                                            @if(in_array($quote->status, ['draft', 'sent']) && $quote->valid_until && $quote->valid_until->isPast())
                                                <flux:menu.item icon="clock" wire:click="markAsExpired({{ $quote->id }})">
                                                    Mark as Expired
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.item icon="pencil" wire:navigate href="{{ route('quotes.edit', $quote) }}">
                                                Edit Quote
                                            </flux:menu.item>

                                            <flux:menu.separator />
                                        @endcan

                                        @can('create-bookings')
                                            @if($quote->status === 'approved' && !$quote->booking)
                                                <flux:menu.separator />
                                                <flux:menu.item icon="arrow-right" wire:click="convertToBooking({{ $quote->id }})">
                                                    Convert to Booking
                                                </flux:menu.item>
                                            @endif
                                        @endcan

                                        @can('create-quotes')
                                            <flux:menu.item icon="document-duplicate" wire:click="duplicateQuote({{ $quote->id }})">
                                                Duplicate
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete-quotes')
                                            <flux:menu.separator />

                                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteQuote({{ $quote->id }})" wire:confirm="Are you sure you want to delete this quote?">
                                                Delete Quote
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        No quotes found. Create your first quote to get started.
                    </div>
                @endforelse

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $quotes->links() }}
                </div>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block">
                <flux:table>
                    <flux:columns>
                        <flux:column>Quote #</flux:column>
                        <flux:column>Client</flux:column>
                        <flux:column>Created By</flux:column>
                        <flux:column>Valid Until</flux:column>
                        <flux:column>Total</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Actions</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse($quotes as $quote)
                            <flux:row :key="$quote->id">
                                <flux:cell>
                                    <strong>{{ $quote->quote_number }}</strong>
                                    @if($quote->version > 1)
                                        <span class="text-xs text-gray-500">(v{{ $quote->version }})</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <div>
                                        <div class="font-medium">{{ $quote->client->name }}</div>
                                        @if($quote->client->company_name)
                                            <div class="text-sm text-gray-500">{{ $quote->client->company_name }}</div>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">{{ $quote->createdBy->name }}</div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">
                                        {{ $quote->valid_until?->format('d M Y') }}
                                        @if($quote->valid_until && $quote->valid_until->isPast() && $quote->status !== 'approved')
                                            <span class="text-xs text-red-600">(Expired)</span>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="font-medium">N${{  number_format($quote->total, 2) }}</div>
                                    <div class="text-xs text-gray-500">excl VAT: N${{  number_format($quote->subtotal, 2) }}</div>
                                </flux:cell>

                                <flux:cell>
                                    <flux:badge
                                        :color="match($quote->status) {
                                            'draft' => 'gray',
                                            'sent' => 'blue',
                                            'approved' => 'green',
                                            'rejected' => 'red',
                                            'expired' => 'orange',
                                            default => 'gray'
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst($quote->status) }}
                                    </flux:badge>
                                </flux:cell>

                                <flux:cell>
                                    <flux:dropdown>
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal">Actions</flux:button>

                                        <flux:menu>
                                            @can('view-quotes')
                                                <flux:menu.item icon="eye" href="{{ route('quotes.pdf.view', $quote) }}" target="_blank">
                                                    View PDF
                                                </flux:menu.item>

                                                <flux:menu.item icon="arrow-down-tray" href="{{ route('quotes.pdf.download', $quote) }}">
                                                    Download PDF
                                                </flux:menu.item>

                                                <flux:menu.separator />
                                            @endcan

                                            @can('edit-quotes')
                                                @if($quote->status === 'draft')
                                                    <flux:menu.item icon="paper-airplane" wire:click="markAsSent({{ $quote->id }})">
                                                        Mark as Sent
                                                    </flux:menu.item>
                                                @endif

                                                @if($quote->status === 'sent')
                                                    <flux:menu.item icon="check-circle" wire:click="markAsApproved({{ $quote->id }})">
                                                        Mark as Approved
                                                    </flux:menu.item>

                                                    <flux:menu.item icon="x-circle" wire:click="markAsRejected({{ $quote->id }})">
                                                        Mark as Rejected
                                                    </flux:menu.item>
                                                @endif

                                                @if(in_array($quote->status, ['draft', 'sent']) && $quote->valid_until && $quote->valid_until->isPast())
                                                    <flux:menu.item icon="clock" wire:click="markAsExpired({{ $quote->id }})">
                                                        Mark as Expired
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.item icon="pencil" wire:navigate href="{{ route('quotes.edit', $quote) }}">
                                                    Edit Quote
                                                </flux:menu.item>

                                                <flux:menu.separator />
                                            @endcan

                                            @can('create-bookings')
                                                @if($quote->status === 'approved' && !$quote->booking)
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="arrow-right" wire:click="convertToBooking({{ $quote->id }})">
                                                        Convert to Booking
                                                    </flux:menu.item>
                                                @endif
                                            @endcan

                                            @can('create-quotes')
                                                <flux:menu.item icon="document-duplicate" wire:click="duplicateQuote({{ $quote->id }})">
                                                    Duplicate
                                                </flux:menu.item>
                                            @endcan

                                            @can('delete-quotes')
                                                <flux:menu.separator />

                                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteQuote({{ $quote->id }})" wire:confirm="Are you sure you want to delete this quote?">
                                                    Delete Quote
                                                </flux:menu.item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:cell>
                            </flux:row>
                        @empty
                            <flux:row>
                                <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                    No quotes found. Create your first quote to get started.
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $quotes->links() }}
                </div>
            </div>
        </div>
    </flux:card>
</div>
