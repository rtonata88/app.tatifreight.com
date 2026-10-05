<?php

use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public Expense $expense;

    #[Validate('required|in:fuel,maintenance,repairs,tolls,insurance,licenses,wages,other')]
    public $category;

    #[Validate('required|numeric|min:0.01')]
    public $amount;

    #[Validate('required|date')]
    public $expense_date;

    #[Validate('required|string|max:500')]
    public $description;

    #[Validate('nullable|exists:vehicles,id')]
    public $vehicle_id;

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id;

    #[Validate('nullable|image|max:2048')]
    public $receipt_upload = null;

    #[Validate('nullable|string')]
    public $notes;

    #[Validate('required|in:pending,approved,rejected')]
    public $status;

    public function mount(Expense $expense)
    {
        $this->expense = Expense::with(['vehicle', 'booking', 'user', 'approvedBy'])->findOrFail($expenseId);

        $this->category = $this->expense->category;
        $this->amount = $this->expense->amount;
        $this->expense_date = $this->expense->expense_date?->format('Y-m-d');
        $this->description = $this->expense->description;
        $this->vehicle_id = $this->expense->vehicle_id;
        $this->booking_id = $this->expense->booking_id;
        $this->notes = $this->expense->notes;
        $this->status = $this->expense->status;
    }

    public function with(): array
    {
        return [
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
            'vehicle_id' => $this->vehicle_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'category' => $this->category,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'description' => $this->description,
            'notes' => $this->notes,
            'status' => $this->status,
        ];

        // Handle receipt upload
        if ($this->receipt_upload) {
            // Delete old receipt if exists
            if ($this->expense->receipt_path) {
                Storage::disk('public')->delete($this->expense->receipt_path);
            }

            $data['receipt_path'] = $this->receipt_upload->store('expenses/receipts', 'public');
        }

        // Update approval info if status changed
        if ($this->status !== $this->expense->status && in_array($this->status, ['approved', 'rejected'])) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $this->expense->update($data);

        $this->dispatch('notify',
            type: 'success',
            message: 'Expense updated successfully!'
        );

        return redirect()->route('expenses.index');
    }

    public function deleteReceipt()
    {
        if ($this->expense->receipt_path) {
            Storage::disk('public')->delete($this->expense->receipt_path);

            $this->expense->update(['receipt_path' => null]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Receipt deleted successfully'
            );

            // Refresh expense
            $this->mount($this->expense->id);
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Expense</flux:heading>

        <div class="flex items-center gap-2">
            <flux:badge
                :color="match($expense->status) {
                    'pending' => 'yellow',
                    'approved' => 'green',
                    'rejected' => 'red',
                    default => 'gray'
                }"
            >
                {{ ucfirst($expense->status) }}
            </flux:badge>
        </div>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Expense Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Category *</flux:label>
                    <flux:select wire:model="category">
                        <option value="">Select category</option>
                        <option value="fuel">Fuel</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="repairs">Repairs</option>
                        <option value="tolls">Tolls</option>
                        <option value="insurance">Insurance</option>
                        <option value="licenses">Licenses</option>
                        <option value="wages">Driver Wages</option>
                        <option value="other">Other</option>
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                <flux:field>
                    <flux:label>Amount (N$) *</flux:label>
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" />
                    <flux:error name="amount" />
                </flux:field>

                <flux:field>
                    <flux:label>Expense Date *</flux:label>
                    <flux:input wire:model="expense_date" type="date" />
                    <flux:error name="expense_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <flux:select wire:model="vehicle_id">
                        <option value="">Select vehicle (optional)</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Related Booking</flux:label>
                    <flux:select wire:model="booking_id">
                        <option value="">Select booking (optional)</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">
                                {{ $booking->booking_number }} - {{ $booking->client->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="booking_id" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description *</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                    <flux:error name="description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Receipt</flux:heading>

            <div class="mt-6 space-y-4">
                @if($expense->receipt_path)
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Current Receipt:</p>
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($expense->receipt_path) }}" class="max-w-sm rounded border border-gray-200">
                            <flux:button
                                type="button"
                                wire:click="deleteReceipt"
                                wire:confirm="Are you sure you want to delete this receipt?"
                                variant="danger"
                                size="sm"
                                class="mt-2"
                            >
                                Delete Receipt
                            </flux:button>
                        </div>
                    </div>
                @endif

                <flux:field>
                    <flux:label>{{ $expense->receipt_path ? 'Replace Receipt' : 'Upload Receipt' }}</flux:label>
                    <flux:input wire:model="receipt_upload" type="file" accept="image/*" />
                    <flux:error name="receipt_upload" />
                    <flux:description>Upload a photo or scan of the receipt (max 2MB)</flux:description>
                </flux:field>

                @if ($receipt_upload)
                    <div>
                        <p class="text-sm text-gray-600 mb-2">New Receipt Preview:</p>
                        <img src="{{ $receipt_upload->temporaryUrl() }}" class="max-w-sm rounded border border-gray-200">
                    </div>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600">Submitted By</p>
                    <p class="text-sm font-medium">{{ $expense->user->name }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Submitted On</p>
                    <p class="text-sm font-medium">{{ $expense->created_at->format('d M Y, H:i') }}</p>
                </div>

                @if($expense->approved_by)
                    <div>
                        <p class="text-sm text-gray-600">{{ $expense->status === 'approved' ? 'Approved' : 'Rejected' }} By</p>
                        <p class="text-sm font-medium">{{ $expense->approvedBy->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">{{ $expense->status === 'approved' ? 'Approved' : 'Rejected' }} On</p>
                        <p class="text-sm font-medium">{{ $expense->approved_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif

                <flux:field class="md:col-span-2">
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Expense
            </flux:button>

            <flux:button wire:navigate href="{{ route('expenses.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
