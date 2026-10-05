<?php

use App\Models\Expense;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';

    public function with(): array
    {
        $user = auth()->user();
        $isDriver = $user->hasRole('driver');

        $query = Expense::with(['vehicle', 'booking', 'user', 'approvedBy'])
            // Filter for drivers: only show expenses submitted by them
            ->when($isDriver, function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($this->search, function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('vehicle', function ($vehicleQuery) {
                      $vehicleQuery->where('reg_number', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter, function ($q) {
                $q->where('category', $this->categoryFilter);
            })
            ->orderBy('expense_date', 'desc');

        $statsQuery = Expense::query();
        if ($isDriver) {
            $statsQuery->where('user_id', $user->id);
        }

        return [
            'expenses' => $query->paginate(10),
            'stats' => [
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
                'total_pending' => (clone $statsQuery)->where('status', 'pending')->sum('amount'),
                'total_approved' => (clone $statsQuery)->where('status', 'approved')->sum('amount'),
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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function approveExpense(int $id): void
    {
        if (auth()->user()->can('edit-expenses')) {
            $expense = Expense::findOrFail($id);

            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense approved successfully'
            );
        }
    }

    public function rejectExpense(int $id): void
    {
        if (auth()->user()->can('edit-expenses')) {
            $expense = Expense::findOrFail($id);

            $expense->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense rejected'
            );
        }
    }

    public function deleteExpense(int $id): void
    {
        if (auth()->user()->can('delete-expenses')) {
            Expense::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Expense deleted successfully'
            );
        }
    }

    public function downloadReceipt(int $id)
    {
        $expense = Expense::findOrFail($id);

        if (!$expense->receipt_path || !Storage::disk('public')->exists($expense->receipt_path)) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Receipt file not found'
            );
            return;
        }

        return response()->download(
            Storage::disk('public')->path($expense->receipt_path),
            basename($expense->receipt_path)
        );
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Expense Management</flux:heading>

        @can('create-expenses')
            <flux:button wire:navigate href="{{ route('expenses.create') }}" icon="plus" variant="primary" class="bg-black hover:bg-gray-800">
                New Expense
            </flux:button>
        @endcan
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card class="bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Approval</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['pending'] }}</p>
                    <p class="text-xs text-gray-500">N${{  number_format($stats['total_pending'], 2) }}</p>
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
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['approved'] }}</p>
                    <p class="text-xs text-gray-500">N${{  number_format($stats['total_approved'], 2) }}</p>
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
    </div>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search expenses..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="categoryFilter" placeholder="Filter by category">
                    <option value="">All Categories</option>
                    <option value="fuel">Fuel</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="repairs">Repairs</option>
                    <option value="tolls">Tolls</option>
                    <option value="insurance">Insurance</option>
                    <option value="licenses">Licenses</option>
                    <option value="wages">Driver Wages</option>
                    <option value="other">Other</option>
                </flux:select>

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </flux:select>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-4">
                @forelse($expenses as $expense)
                    <flux:card>
                        <div class="space-y-3">
                            {{-- Header with Amount and Status --}}
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-2xl text-gray-900">N${{ number_format($expense->amount, 2) }}</div>
                                    <div class="text-sm text-gray-600">{{ $expense->expense_date->format('d M Y') }}</div>
                                </div>
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
                            </div>

                            {{-- Description and Category --}}
                            <div class="border-t pt-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900">{{ $expense->description }}</div>
                                            @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                                                <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Receipt available">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        @if($expense->booking_id)
                                            <div class="text-xs text-gray-500 mt-1">Booking: {{ $expense->booking->booking_number }}</div>
                                        @endif
                                    </div>
                                    <flux:badge color="gray" size="sm">
                                        {{ ucfirst($expense->category) }}
                                    </flux:badge>
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 border-t pt-3">
                                <div>
                                    <div class="text-xs text-gray-500">Vehicle</div>
                                    @if($expense->vehicle)
                                        <div class="text-sm font-medium">{{ $expense->vehicle->reg_number }}</div>
                                    @else
                                        <div class="text-sm text-gray-400">-</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Submitted By</div>
                                    <div class="text-sm font-medium">{{ $expense->user->name }}</div>
                                </div>
                            </div>

                            @if($expense->status === 'approved' && $expense->approvedBy)
                                <div class="border-t pt-3">
                                    <div class="text-xs text-gray-500">Approved By</div>
                                    <div class="text-sm">{{ $expense->approvedBy->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $expense->approved_at->format('d M Y H:i') }}</div>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="border-t pt-3 flex flex-wrap gap-2">
                                @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                                    <flux:button
                                        wire:click="downloadReceipt({{ $expense->id }})"
                                        size="sm"
                                        variant="ghost"
                                        icon="arrow-down-tray"
                                        class="flex-1"
                                    >
                                        Download Receipt
                                    </flux:button>
                                @endif

                                @can('edit-expenses')
                                    @if($expense->status === 'pending')
                                        <flux:button
                                            wire:click="approveExpense({{ $expense->id }})"
                                            size="sm"
                                            variant="primary"
                                            class="flex-1"
                                        >
                                            Approve
                                        </flux:button>

                                        <flux:button
                                            wire:click="rejectExpense({{ $expense->id }})"
                                            size="sm"
                                            variant="danger"
                                            class="flex-1"
                                        >
                                            Reject
                                        </flux:button>
                                    @endif

                                    <flux:button
                                        wire:navigate
                                        href="{{ route('expenses.edit', $expense) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        class="flex-1"
                                    >
                                        Edit
                                    </flux:button>
                                @endcan

                                @can('delete-expenses')
                                    <flux:button
                                        wire:click="deleteExpense({{ $expense->id }})"
                                        wire:confirm="Are you sure you want to delete this expense?"
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                    >
                                        Delete
                                    </flux:button>
                                @endcan
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        No expenses found. Create your first expense to get started.
                    </div>
                @endforelse
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block">
                <flux:table>
                    <flux:columns>
                        <flux:column>Date</flux:column>
                        <flux:column>Category</flux:column>
                        <flux:column>Description</flux:column>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Submitted By</flux:column>
                        <flux:column>Amount</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Actions</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse($expenses as $expense)
                            <flux:row :key="$expense->id">
                                <flux:cell>
                                    <div class="text-sm">{{ $expense->expense_date->format('d M Y') }}</div>
                                </flux:cell>

                                <flux:cell>
                                    <flux:badge color="gray" size="sm">
                                        {{ ucfirst($expense->category) }}
                                    </flux:badge>
                                </flux:cell>

                                <flux:cell>
                                    <div class="max-w-xs">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium">{{ $expense->description }}</p>
                                            @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Receipt available">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        @if($expense->booking_id)
                                            <p class="text-xs text-gray-500">Booking: {{ $expense->booking->booking_number }}</p>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    @if($expense->vehicle)
                                        <div class="text-sm">{{ $expense->vehicle->reg_number }}</div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">{{ $expense->user->name }}</div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="font-medium">N${{  number_format($expense->amount, 2) }}</div>
                                </flux:cell>

                                <flux:cell>
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
                                </flux:cell>

                                <flux:cell>
                                    <flux:dropdown position="left" align="start">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                        <flux:menu class="min-w-32">
                                            @if($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path))
                                                <flux:menu.item
                                                    wire:click="downloadReceipt({{ $expense->id }})"
                                                    icon="arrow-down-tray"
                                                >
                                                    Download Receipt
                                                </flux:menu.item>

                                                <flux:menu.separator />
                                            @endif

                                            @can('edit-expenses')
                                                @if($expense->status === 'pending')
                                                    <flux:menu.item
                                                        wire:click="approveExpense({{ $expense->id }})"
                                                        icon="check-circle"
                                                    >
                                                        Approve
                                                    </flux:menu.item>

                                                    <flux:menu.item
                                                        wire:click="rejectExpense({{ $expense->id }})"
                                                        icon="x-circle"
                                                        variant="danger"
                                                    >
                                                        Reject
                                                    </flux:menu.item>

                                                    <flux:menu.separator />
                                                @endif

                                                <flux:menu.item
                                                    wire:navigate
                                                    href="{{ route('expenses.show', $expense) }}"
                                                    icon="eye"
                                                >
                                                    View
                                                </flux:menu.item>

                                                <flux:menu.item
                                                    wire:navigate
                                                    href="{{ route('expenses.edit', $expense) }}"
                                                    icon="pencil"
                                                >
                                                    Edit
                                                </flux:menu.item>
                                            @endcan

                                            @can('delete-expenses')
                                                <flux:menu.separator />
                                                <flux:menu.item
                                                    wire:click="deleteExpense({{ $expense->id }})"
                                                    wire:confirm="Are you sure you want to delete this expense?"
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
                                    No expenses found. Create your first expense to get started.
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $expenses->links() }}
            </div>
        </div>
    </flux:card>
</div>
