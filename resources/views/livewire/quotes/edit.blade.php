<?php

use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    public Quote $quote;

    #[Validate('required|exists:clients,id')]
    public $client_id;

    #[Validate('nullable|string')]
    public $description;

    #[Validate('required|date')]
    public $valid_until;

    #[Validate('nullable|string')]
    public $terms_conditions;

    #[Validate('nullable|string')]
    public $notes;

    #[Validate('required|in:draft,sent,approved,rejected,expired')]
    public $status;

    public $lineItems = [];
    public $subtotal = 0;
    public $tax_amount = 0;
    public $total = 0;
    public $tax_rate = 15;

    public function mount(Quote $quote)
    {
        $this->quote = $quote;

        $this->client_id = $this->quote->client_id;
        $this->description = $this->quote->description;
        $this->valid_until = $this->quote->valid_until?->format('Y-m-d');
        $this->terms_conditions = $this->quote->terms_conditions;
        $this->notes = $this->quote->notes;
        $this->status = $this->quote->status;
        $this->subtotal = (float)$this->quote->subtotal;
        $this->tax_amount = (float)$this->quote->tax_amount;
        $this->total = (float)$this->quote->total;

        // Load existing line items
        foreach ($this->quote->lineItems as $item) {
            $this->lineItems[] = [
                'id' => $item->id,
                'vehicle_id' => $item->vehicle_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float)$item->unit_price,
                'amount' => (float)$item->amount,
                'unit' => $item->unit,
            ];
        }
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')->where('status', 'available')->orderBy('reg_number')->get(),
            'hasBooking' => $this->quote->booking()->exists(),
        ];
    }

    public function addLineItem()
    {
        $this->lineItems[] = [
            'id' => null,
            'vehicle_id' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'amount' => 0,
            'unit' => 'trip',
        ];
    }

    public function removeLineItem($index)
    {
        // If line item has an ID, mark it for deletion
        if (isset($this->lineItems[$index]['id'])) {
            QuoteLineItem::find($this->lineItems[$index]['id'])->delete();
        }

        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
        $this->calculateTotals();
    }

    public function calculateLineAmount($index)
    {
        if (isset($this->lineItems[$index])) {
            $quantity = (float)($this->lineItems[$index]['quantity'] ?? 0);
            $unitPrice = (float)($this->lineItems[$index]['unit_price'] ?? 0);
            $this->lineItems[$index]['amount'] = $quantity * $unitPrice;
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = 0;

        foreach ($this->lineItems as $item) {
            $this->subtotal += (float)($item['amount'] ?? 0);
        }

        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount;
    }

    public function loadVehicleRate($index)
    {
        if (isset($this->lineItems[$index]['vehicle_id']) && $this->lineItems[$index]['vehicle_id']) {
            $vehicle = Vehicle::with('vehicleType')->find($this->lineItems[$index]['vehicle_id']);

            if ($vehicle) {
                $this->lineItems[$index]['description'] = $vehicle->vehicleType->name . ' - ' . $vehicle->reg_number;

                if ($vehicle->vehicleType->base_rate_daily) {
                    $this->lineItems[$index]['unit_price'] = $vehicle->vehicleType->base_rate_daily;
                }

                $this->calculateLineAmount($index);
            }
        }
    }

    public function save()
    {
        $this->validate();

        if (empty($this->lineItems)) {
            $this->dispatch('notify',
                type: 'error',
                message: 'Please add at least one line item'
            );
            return;
        }

        // Update quote
        $this->quote->update([
            'client_id' => $this->client_id,
            'status' => $this->status,
            'valid_until' => $this->valid_until,
            'description' => $this->description,
            'terms_conditions' => $this->terms_conditions,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'notes' => $this->notes,
        ]);

        // Update or create line items
        foreach ($this->lineItems as $item) {
            if ($item['id']) {
                // Update existing line item
                QuoteLineItem::find($item['id'])->update([
                    'vehicle_id' => $item['vehicle_id'] ?: null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'unit' => $item['unit'],
                ]);
            } else {
                // Create new line item
                $this->quote->lineItems()->create([
                    'vehicle_id' => $item['vehicle_id'] ?: null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'unit' => $item['unit'],
                ]);
            }
        }

        $this->dispatch('notify',
            type: 'success',
            message: 'Quote updated successfully!'
        );

        return redirect()->route('quotes.index');
    }

    public function convertToBooking()
    {
        if (!auth()->user()->can('create-bookings')) {
            $this->dispatch('notify',
                type: 'error',
                message: 'You do not have permission to create bookings'
            );
            return;
        }

        if ($this->quote->status !== 'approved') {
            $this->dispatch('notify',
                type: 'error',
                message: 'Only approved quotes can be converted to bookings'
            );
            return;
        }

        if ($this->quote->booking()->exists()) {
            $this->dispatch('notify',
                type: 'error',
                message: 'This quote has already been converted to a booking'
            );
            return;
        }

        // Get the first line item with a vehicle to determine the vehicle for booking
        $vehicleItem = collect($this->lineItems)->firstWhere(function ($item) {
            return !empty($item['vehicle_id']);
        });

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
            'client_id' => $this->quote->client_id,
            'vehicle_id' => $vehicleItem['vehicle_id'],
            'quote_id' => $this->quote->id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'notes' => $this->quote->description,
        ]);

        // Note: Vehicle status will be updated when booking status changes to 'in_progress'
        // For immediate bookings (starting now), consider setting status to 'in_progress'

        $this->dispatch('notify',
            type: 'success',
            message: 'Booking created successfully from quote!'
        );

        return redirect()->route('bookings.edit', $booking->id);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Quote: {{ $quote->quote_number }}</flux:heading>

        <div class="flex items-center gap-2">
            <flux:badge
                :color="match($quote->status) {
                    'draft' => 'gray',
                    'sent' => 'blue',
                    'approved' => 'green',
                    'rejected' => 'red',
                    'expired' => 'orange',
                    default => 'gray'
                }"
            >
                {{ ucfirst($quote->status) }}
            </flux:badge>

            @can('view-quotes')
                <flux:button
                    href="{{ route('quotes.pdf.view', $quote) }}"
                    target="_blank"
                    variant="ghost"
                    icon="eye"
                >
                    View PDF
                </flux:button>

                <flux:button
                    href="{{ route('quotes.pdf.download', $quote) }}"
                    variant="ghost"
                    icon="arrow-down-tray"
                >
                    Download PDF
                </flux:button>
            @endcan

            @if($quote->status === 'approved' && !$hasBooking)
                <flux:button
                    wire:click="convertToBooking"
                    variant="primary"
                    icon="arrow-right"
                >
                    Convert to Booking
                </flux:button>
            @endif

            @if($hasBooking)
                <span class="text-sm text-green-600">✓ Converted to Booking</span>
            @endif
        </div>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Quote Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label>Client *</flux:label>
                    <flux:select wire:model="client_id">
                        <option value="">Select a client</option>
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
                    <flux:label>Valid Until *</flux:label>
                    <flux:input wire:model="valid_until" type="date" />
                    <flux:error name="valid_until" />
                </flux:field>

                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field class="md:col-span-3">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="2" />
                    <flux:error name="description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Line Items</flux:heading>
                <flux:button type="button" wire:click="addLineItem" variant="ghost" icon="plus" size="sm">
                    Add Line
                </flux:button>
            </div>

            {{-- Excel-style Table --}}
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-300" style="font-size: 0.875rem;">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700" style="width: 3%;">#</th>
                            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700" style="width: 22%;">Vehicle</th>
                            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700" style="width: 25%;">Description</th>
                            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700" style="width: 12%;">Unit</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700" style="width: 10%;">Qty</th>
                            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700" style="width: 13%;">Unit Price</th>
                            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700" style="width: 13%;">Amount</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700" style="width: 2%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lineItems as $index => $item)
                            <tr wire:key="line-{{ $index }}" class="hover:bg-gray-50">
                                <td class="border border-gray-300 px-2 py-2 text-center text-gray-600 bg-gray-50">
                                    {{ $index + 1 }}
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <select 
                                        wire:model.live="lineItems.{{ $index }}.vehicle_id" 
                                        wire:change="loadVehicleRate({{ $index }})"
                                        class="w-full border-0 focus:ring-1 focus:ring-blue-500 rounded px-2 py-1.5 text-sm"
                                    >
                                        <option value="">Select vehicle</option>
                                        @foreach($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}">
                                                {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("lineItems.{$index}.vehicle_id") 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <input 
                                        wire:model="lineItems.{{ $index }}.description"
                                        type="text"
                                        placeholder="Item description"
                                        class="w-full border-0 focus:ring-1 focus:ring-blue-500 rounded px-2 py-1.5 text-sm"
                                    />
                                    @error("lineItems.{$index}.description") 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <select 
                                        wire:model.live="lineItems.{{ $index }}.unit"
                                        class="w-full border-0 focus:ring-1 focus:ring-blue-500 rounded px-2 py-1.5 text-sm"
                                    >
                                        <option value="trip">Trip</option>
                                        <option value="day">Day</option>
                                        <option value="hour">Hour</option>
                                        <option value="km">Km</option>
                                        <option value="tonne">Tonne</option>
                                        <option value="load">Load</option>
                                        <option value="pallet">Pallet</option>
                                        <option value="container">Container</option>
                                        <option value="cbm">CBM</option>
                                        <option value="item">Item</option>
                                        <option value="week">Week</option>
                                        <option value="month">Month</option>
                                    </select>
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <input 
                                        wire:model.blur="lineItems.{{ $index }}.quantity"
                                        wire:change="calculateLineAmount({{ $index }})"
                                        type="number"
                                        min="1"
                                        class="w-full border-0 focus:ring-1 focus:ring-blue-500 rounded px-2 py-1.5 text-sm text-center"
                                    />
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <input 
                                        wire:model.blur="lineItems.{{ $index }}.unit_price"
                                        wire:change="calculateLineAmount({{ $index }})"
                                        type="number"
                                        step="0.01"
                                        class="w-full border-0 focus:ring-1 focus:ring-blue-500 rounded px-2 py-1.5 text-sm text-right"
                                    />
                                </td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <input 
                                        wire:model="lineItems.{{ $index }}.amount"
                                        type="number"
                                        step="0.01"
                                        readonly
                                        class="w-full border-0 bg-gray-50 px-2 py-1.5 text-sm text-right font-medium"
                                    />
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center">
                                    @if(count($lineItems) > 1)
                                        <button
                                            type="button"
                                            wire:click="removeLineItem({{ $index }})"
                                            class="text-red-600 hover:text-red-800"
                                            title="Remove line"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="border border-gray-300 px-4 py-8 text-center text-gray-500">
                                    No line items added yet. Click "Add Line" to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totals Summary --}}
            <div class="mt-6 border-t border-gray-200 pt-6">
                <div class="max-w-md ml-auto space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium">N${{  number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">VAT ({{ $tax_rate }}%):</span>
                        <span class="font-medium">N${{  number_format($tax_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                        <span>Total:</span>
                        <span>N${{  number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Terms & Conditions</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Terms & Conditions</flux:label>
                    <flux:textarea wire:model="terms_conditions" rows="6" />
                    <flux:error name="terms_conditions" />
                </flux:field>
            </div>
        </flux:card>

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

        @if($quote->sent_at || $quote->approved_at)
            <flux:card>
                <flux:heading size="lg">Timeline</flux:heading>

                <div class="mt-6 space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Created</p>
                        <p class="text-sm text-gray-600">{{ $quote->created_at->format('d M Y, H:i') }} by {{ $quote->createdBy->name }}</p>
                    </div>

                    @if($quote->sent_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Sent to Client</p>
                            <p class="text-sm text-gray-600">{{ $quote->sent_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif

                    @if($quote->approved_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Approved</p>
                            <p class="text-sm text-gray-600">{{ $quote->approved_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Quote
            </flux:button>

            <flux:button wire:navigate href="{{ route('quotes.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
