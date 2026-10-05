<?php

use App\Models\Expense;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {

    public Expense $expense;

    public function mount(Expense $expense)
    {
        $this->expense = $expense;
    }

    public function with(): array
    {
        $expense = Expense::with([
            'vehicle.vehicleType',
            'booking.client',
            'user',
            'approvedBy',
        ])->findOrFail($this->expense->id);

        return [
            'expense' => $expense,
        ];
    }

    public function approveExpense(): void
    {
        if (auth()->user()->can('edit-expenses')) {
            $this->expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense approved successfully'
            );

            $this->expense->refresh();
        }
    }

    public function rejectExpense(): void
    {
        if (auth()->user()->can('edit-expenses')) {
            $this->expense->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense rejected'
            );

            $this->expense->refresh();
        }
    }

    public function deleteExpense(): void
    {
        if (auth()->user()->can('delete-expenses')) {
            $this->expense->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense deleted successfully'
            );

            $this->redirect(route('expenses.index'), navigate: true);
        }
    }

    public function downloadReceipt()
    {
        if (!$this->expense->receipt_path || !Storage::disk('public')->exists($this->expense->receipt_path)) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Receipt file not found'
            );
            return;
        }

        return response()->download(
            Storage::disk('public')->path($this->expense->receipt_path),
            basename($this->expense->receipt_path)
        );
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Expense Details</flux:heading>

        <div class="flex gap-3">
            <flux:button wire:navigate href="{{ route('expenses.index') }}" variant="ghost" icon="arrow-left">
                Back to Expenses
            </flux:button>
            @can('edit-expenses')
                <flux:button wire:navigate href="{{ route('expenses.edit', $expense) }}" variant="primary" icon="pencil">
                    Edit Expense
                </flux:button>
            @endcan
        </div>
    </flux:header>

    <div class="mt-6 space-y-6">
        {{-- Expense Header Card --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-4">
                        <h2 class="text-3xl font-bold">N${{ number_format($expense->amount, 2) }}</h2>
                        <flux:badge
                            :color="match($expense->status) {
                                'pending' => 'yellow',
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'gray'
                            }"
                            size="lg"
                        >
                            {{ ucfirst($expense->status) }}
                        </flux:badge>
                    </div>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">{{ $expense->description }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        <flux:badge color="gray" size="sm">{{ ucfirst($expense->category) }}</flux:badge>
                    </p>
                </div>
                @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                    <div class="flex flex-col items-end gap-2">
                        <flux:button
                            wire:click="downloadReceipt"
                            variant="ghost"
                            icon="arrow-down-tray"
                            size="sm"
                        >
                            Download Receipt
                        </flux:button>
                    </div>
                @endif
            </div>
        </flux:card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Expense Details --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <flux:card>
                    <flux:heading size="lg">Expense Information</flux:heading>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Expense Date</span>
                            <p class="font-medium">{{ $expense->expense_date->format('d M Y') }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Category</span>
                            <p class="font-medium">
                                <flux:badge color="gray" size="sm">{{ ucfirst($expense->category) }}</flux:badge>
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Amount</span>
                            <p class="font-medium text-lg">N${{ number_format($expense->amount, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Status</span>
                            <p class="font-medium">
                                <flux:badge
                                    :color="match($expense->status) {
                                        'pending' => 'yellow',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($expense->status) }}
                                </flux:badge>
                            </p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-sm text-gray-500">Description</span>
                            <p class="font-medium">{{ $expense->description }}</p>
                        </div>
                    </div>
                </flux:card>

                {{-- Related Information --}}
                <flux:card>
                    <flux:heading size="lg">Related Information</flux:heading>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        @if($expense->vehicle)
                            <div>
                                <span class="text-sm text-gray-500">Vehicle</span>
                                <p class="font-medium">
                                    <a href="{{ route('vehicles.show', $expense->vehicle) }}" class="text-blue-600 hover:underline" wire:navigate>
                                        {{ $expense->vehicle->reg_number }}
                                    </a>
                                </p>
                                @if($expense->vehicle->vehicleType)
                                    <p class="text-xs text-gray-500">{{ $expense->vehicle->vehicleType->name }}</p>
                                @endif
                            </div>
                        @endif

                        @if($expense->booking)
                            <div>
                                <span class="text-sm text-gray-500">Booking</span>
                                <p class="font-medium">
                                    <a href="{{ route('bookings.index') }}" class="text-blue-600 hover:underline" wire:navigate>
                                        {{ $expense->booking->booking_number }}
                                    </a>
                                </p>
                                @if($expense->booking->client)
                                    <p class="text-xs text-gray-500">{{ $expense->booking->client->company_name ?: $expense->booking->client->name }}</p>
                                @endif
                            </div>
                        @endif

                        <div>
                            <span class="text-sm text-gray-500">Submitted By</span>
                            <p class="font-medium">{{ $expense->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $expense->created_at->format('d M Y, H:i') }}</p>
                        </div>

                        @if($expense->approvedBy)
                            <div>
                                <span class="text-sm text-gray-500">Approved By</span>
                                <p class="font-medium">{{ $expense->approvedBy->name }}</p>
                                @if($expense->approved_at)
                                    <p class="text-xs text-gray-500">{{ $expense->approved_at->format('d M Y, H:i') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </flux:card>

                @if($expense->notes)
                    <flux:card>
                        <flux:heading size="lg">Notes</flux:heading>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $expense->notes }}</p>
                    </flux:card>
                @endif
            </div>

            {{-- Right Column: Receipt & Actions --}}
            <div class="space-y-6">
                {{-- Receipt Preview --}}
                @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                    <flux:card>
                        <flux:heading size="lg">Receipt</flux:heading>
                        <div class="mt-4">
                            @php
                                $receiptExtension = strtolower(pathinfo($expense->receipt_path, PATHINFO_EXTENSION));
                                $isImage = in_array($receiptExtension, ['jpg', 'jpeg', 'png', 'gif']);
                            @endphp

                            @if($isImage)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <img 
                                        src="{{ Storage::url($expense->receipt_path) }}" 
                                        alt="Receipt" 
                                        class="w-full h-auto max-h-96 object-contain"
                                    >
                                </div>
                            @else
                                <div class="p-8 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">PDF Receipt</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ strtoupper($receiptExtension) }} File</p>
                                </div>
                            @endif

                            <div class="mt-4">
                                <flux:button
                                    wire:click="downloadReceipt"
                                    variant="primary"
                                    icon="arrow-down-tray"
                                    class="w-full"
                                >
                                    Download Receipt
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @endif

                {{-- Quick Actions --}}
                @can('edit-expenses')
                    <flux:card>
                        <flux:heading size="lg">Quick Actions</flux:heading>
                        <div class="mt-4 space-y-2">
                            @if($expense->status === 'pending')
                                <flux:button
                                    wire:click="approveExpense"
                                    variant="primary"
                                    icon="check-circle"
                                    class="w-full"
                                >
                                    Approve Expense
                                </flux:button>

                                <flux:button
                                    wire:click="rejectExpense"
                                    variant="danger"
                                    icon="x-circle"
                                    class="w-full"
                                >
                                    Reject Expense
                                </flux:button>
                            @endif

                            <flux:button
                                wire:navigate
                                href="{{ route('expenses.edit', $expense) }}"
                                variant="ghost"
                                icon="pencil"
                                class="w-full"
                            >
                                Edit Expense
                            </flux:button>

                            @can('delete-expenses')
                                <flux:button
                                    wire:click="deleteExpense"
                                    wire:confirm="Are you sure you want to delete this expense?"
                                    variant="danger"
                                    icon="trash"
                                    class="w-full"
                                >
                                    Delete Expense
                                </flux:button>
                            @endcan
                        </div>
                    </flux:card>
                @endcan

                {{-- Status History --}}
                <flux:card>
                    <flux:heading size="lg">Status History</flux:heading>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Created</p>
                                <p class="text-xs text-gray-500">{{ $expense->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $expense->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>

                        @if($expense->approvedBy)
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-2 h-2 rounded-full {{ $expense->status === 'approved' ? 'bg-green-500' : 'bg-red-500' }} mt-2"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{{ ucfirst($expense->status) }}</p>
                                    <p class="text-xs text-gray-500">{{ $expense->approvedBy->name }}</p>
                                    @if($expense->approved_at)
                                        <p class="text-xs text-gray-400">{{ $expense->approved_at->format('d M Y, H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</div>

