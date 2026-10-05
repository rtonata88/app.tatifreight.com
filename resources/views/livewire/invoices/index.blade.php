<?php

use App\Models\Invoice;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $query = Invoice::with(['client', 'booking', 'createdBy'])
            ->when($this->search, function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                  ->orWhere('company_name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');

        $totalUnpaid = Invoice::where('status', 'unpaid')->sum('amount_due');
        $totalOverdue = Invoice::where('status', 'overdue')->sum('amount_due');

        return [
            'invoices' => $query->paginate(10),
            'stats' => [
                'draft' => Invoice::where('status', 'draft')->count(),
                'sent' => Invoice::where('status', 'sent')->count(),
                'unpaid' => Invoice::where('status', 'unpaid')->count(),
                'partial' => Invoice::where('status', 'partial')->count(),
                'paid' => Invoice::where('status', 'paid')->count(),
                'overdue' => Invoice::where('status', 'overdue')->count(),
                'total_unpaid' => $totalUnpaid,
                'total_overdue' => $totalOverdue,
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
        if (auth()->user()->can('edit-invoices')) {
            $invoice = Invoice::findOrFail($id);

            $invoice->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Invoice marked as sent'
            );
        }
    }

    public function markAsPaid(int $id): void
    {
        if (auth()->user()->can('edit-invoices')) {
            $invoice = Invoice::findOrFail($id);

            $invoice->update([
                'status' => 'paid',
                'amount_paid' => $invoice->total,
                'amount_due' => 0,
                'paid_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Invoice marked as paid'
            );
        }
    }

    public function deleteInvoice(int $id): void
    {
        if (auth()->user()->can('delete-invoices')) {
            Invoice::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Invoice deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Invoices</flux:heading>

        @can('create-invoices')
            <flux:button wire:navigate href="{{ route('invoices.create') }}" icon="plus">
                New Invoice
            </flux:button>
        @endcan
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
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

        <flux:card class="bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Unpaid</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['unpaid'] }}</p>
                    <p class="text-xs text-gray-500">N${{  number_format($stats['total_unpaid'], 2) }}</p>
                </div>
                <div class="text-yellow-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-red-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Overdue</p>
                    <p class="text-2xl font-bold text-red-700">{{ $stats['overdue'] }}</p>
                    <p class="text-xs text-gray-500">N${{  number_format($stats['total_overdue'], 2) }}</p>
                </div>
                <div class="text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['paid'] }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                    placeholder="Search invoices or clients..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </flux:select>
            </div>

            {{-- Invoices Table --}}
            <flux:table>
                <flux:columns>
                    <flux:column>Invoice #</flux:column>
                    <flux:column>Client</flux:column>
                    <flux:column>Invoice Date</flux:column>
                    <flux:column>Due Date</flux:column>
                    <flux:column>Total</flux:column>
                    <flux:column>Paid</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($invoices as $invoice)
                        <flux:row :key="$invoice->id">
                            <flux:cell>
                                <strong>{{ $invoice->invoice_number }}</strong>
                                @if($invoice->booking_id)
                                    <div class="text-xs text-gray-500">{{ $invoice->booking->booking_number }}</div>
                                @endif
                            </flux:cell>

                            <flux:cell>
                                <div>
                                    <div class="font-medium">{{ $invoice->client->name }}</div>
                                    @if($invoice->client->company_name)
                                        <div class="text-sm text-gray-500">{{ $invoice->client->company_name }}</div>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">{{ $invoice->invoice_date?->format('d M Y') }}</div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    {{ $invoice->due_date?->format('d M Y') }}
                                    @if($invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['paid']))
                                        <span class="text-xs text-red-600">(Overdue)</span>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="font-medium">N${{  number_format($invoice->total, 2) }}</div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    <span class="font-medium">N${{  number_format($invoice->amount_paid, 2) }}</span>
                                    @if($invoice->amount_due > 0)
                                        <div class="text-xs text-red-600">Due: N${{  number_format($invoice->amount_due, 2) }}</div>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <flux:badge
                                    :color="match($invoice->status) {
                                        'draft' => 'gray',
                                        'sent' => 'blue',
                                        'unpaid' => 'yellow',
                                        'partial' => 'orange',
                                        'paid' => 'green',
                                        'overdue' => 'red',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($invoice->status) }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                <flux:dropdown position="left" align="start">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                    <flux:menu class="min-w-32">
                                        @can('view-invoices')
                                            <flux:menu.item href="{{ route('invoices.pdf.view', $invoice) }}" target="_blank" icon="eye">
                                                View PDF
                                            </flux:menu.item>

                                            <flux:menu.item href="{{ route('invoices.pdf.download', $invoice) }}" icon="arrow-down-tray">
                                                Download PDF
                                            </flux:menu.item>

                                            <flux:menu.separator />
                                        @endcan

                                        @can('edit-invoices')
                                            <flux:menu.item wire:navigate href="{{ route('invoices.edit', $invoice) }}" icon="pencil">
                                                Edit
                                            </flux:menu.item>

                                            @if($invoice->status === 'draft')
                                                <flux:menu.item wire:click="markAsSent({{ $invoice->id }})" icon="paper-airplane">
                                                    Send Invoice
                                                </flux:menu.item>
                                            @endif

                                            @if(in_array($invoice->status, ['sent', 'unpaid', 'partial', 'overdue']))
                                                <flux:menu.item wire:click="markAsPaid({{ $invoice->id }})" icon="check-circle">
                                                    Mark as Paid
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.separator />
                                        @endcan

                                        @can('delete-invoices')
                                            <flux:menu.item 
                                                wire:click="deleteInvoice({{ $invoice->id }})" 
                                                wire:confirm="Are you sure you want to delete this invoice?" 
                                                icon="trash"
                                                variant="danger"
                                            >
                                                Delete
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="8" class="text-center text-gray-500 py-8">
                                No invoices found. Create your first invoice to get started.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </flux:card>
</div>
