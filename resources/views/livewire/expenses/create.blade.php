<?php

use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    #[Validate('required|in:fuel,maintenance,repairs,tolls,insurance,licenses,wages,other')]
    public $category = '';

    #[Validate('required|numeric|min:0.01')]
    public $amount = '';

    #[Validate('required|date')]
    public $expense_date = '';

    #[Validate('required|string|max:500')]
    public $description = '';

    #[Validate('nullable|exists:vehicles,id')]
    public $vehicle_id = '';

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id = '';

    #[Validate('nullable|image|max:2048')]
    public $receipt_upload = null;

    #[Validate('nullable|string')]
    public $notes = '';

    public function mount()
    {
        $this->expense_date = now()->format('Y-m-d');
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

        $receiptPath = null;

        if ($this->receipt_upload) {
            $receiptPath = $this->receipt_upload->store('expenses/receipts', 'public');
        }

        Expense::create([
            'vehicle_id' => $this->vehicle_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'user_id' => auth()->id(),
            'category' => $this->category,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'description' => $this->description,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'notes' => $this->notes,
        ]);

        $this->dispatch('notify',
            type: 'success',
            message: 'Expense submitted successfully!'
        );

        return redirect()->route('expenses.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Submit New Expense</flux:heading>
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
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" placeholder="0.00" />
                    <flux:error name="amount" />
                </flux:field>

                <flux:field>
                    <flux:label>Expense Date *</flux:label>
                    <flux:input wire:model="expense_date" type="date" />
                    <flux:error name="expense_date" />
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
                    <flux:description>Link expense to a specific vehicle</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Related Booking</flux:label>
                    <flux:select wire:model="booking_id">
                        <option value="">Select booking (optional)</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">
                                {{ $booking->booking_number }} - {{ $booking->client->name }}
                                ({{ $booking->start_date->format('d M Y') }})
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="booking_id" />
                    <flux:description>Link expense to a specific booking</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description *</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Describe the expense..." />
                    <flux:error name="description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Receipt Upload</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Receipt/Invoice Image</flux:label>
                    <flux:input wire:model="receipt_upload" type="file" accept="image/*" />
                    <flux:error name="receipt_upload" />
                    <flux:description>Upload a photo or scan of the receipt (max 2MB)</flux:description>
                </flux:field>

                @if ($receipt_upload)
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">Preview:</p>
                        <img src="{{ $receipt_upload->temporaryUrl() }}" class="max-w-sm rounded border border-gray-200">
                    </div>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Any additional information..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Submit Expense
            </flux:button>

            <flux:button wire:navigate href="{{ route('expenses.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
