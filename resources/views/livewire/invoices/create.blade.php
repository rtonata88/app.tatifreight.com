<?php

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Client;
use App\Models\Booking;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

    #[Validate('nullable|exists:company_bank_accounts,id')]
    public $company_bank_account_id = '';

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id = '';

    #[Validate('required|date')]
    public $invoice_date = '';

    #[Validate('required|date|after_or_equal:invoice_date')]
    public $due_date = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('nullable|string')]
    public $notes = '';

    public $lineItems = [];
    public $subtotal = 0;
    public $tax_amount = 0;
    public $total = 0;
    public $tax_rate = 15;

    public function mount()
    {
        // Default dates
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');

        // Set default bank account to primary
        $primaryAccount = \App\Models\CompanyBankAccount::primary();
        if ($primaryAccount) {
            $this->company_bank_account_id = $primaryAccount->id;
        }

        // Add one empty line item
        $this->addLineItem();
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'bookings' => Booking::with(['client', 'vehicle'])
                ->where('status', 'completed')
                ->whereDoesntHave('invoice')
                ->orderBy('created_at', 'desc')
                ->get(),
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get(),
            'bankAccounts' => \App\Models\CompanyBankAccount::active(),
        ];
    }

    public function addLineItem()
    {
        $this->lineItems[] = [
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

    public function loadBookingDetails()
    {
        if ($this->booking_id) {
            $booking = Booking::with(['client', 'vehicle.vehicleType'])->find($this->booking_id);

            if ($booking) {
                // Set client
                $this->client_id = $booking->client_id;

                // Clear existing line items
                $this->lineItems = [];

                // Add rental line item
                $days = $booking->start_date->diffInDays($booking->end_date) ?: 1;
                $unitPrice = round((float)($booking->vehicle->vehicleType->base_rate_daily ?? 0), 2);
                $amount = round($days * $unitPrice, 2);

                $this->lineItems[] = [
                    'vehicle_id' => $booking->vehicle_id,
                    'description' => "Vehicle Rental - {$booking->vehicle->vehicleType->name} ({$booking->vehicle->reg_number})",
                    'quantity' => $days,
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'amount' => number_format($amount, 2, '.', ''),
                    'unit' => 'day',
                ];

                // Add MDC if applicable
                if ($booking->distance_km && $booking->load_weight && $booking->vehicle->tare_weight) {
                    $totalMass = round((float)$booking->vehicle->tare_weight + (float)$booking->load_weight, 2);
                    $mdcAmount = round(($totalMass * $booking->distance_km * ((float)($booking->vehicle->vehicleType->base_rate_per_km ?? 0))) / 100, 2);

                    $this->lineItems[] = [
                        'vehicle_id' => $booking->vehicle_id,
                        'description' => "Mass Distance Charge - {$booking->distance_km}km × " . number_format($totalMass, 2) . "t",
                        'quantity' => 1,
                        'unit_price' => number_format($mdcAmount, 2, '.', ''),
                        'amount' => number_format($mdcAmount, 2, '.', ''),
                        'unit' => 'trip',
                    ];
                }

                $this->description = "Invoice for booking {$booking->booking_number}";
                $this->calculateTotals();

                $this->dispatch('notify',
                    type: 'success',
                    message: 'Booking details loaded successfully'
                );
            }
        }
    }

    public function updatedClientId($value)
    {
        if ($value) {
            $client = Client::find($value);
            if ($client && $client->payment_terms_days) {
                $this->due_date = now()->addDays($client->payment_terms_days)->format('Y-m-d');
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

        // Generate invoice number
        $lastInvoice = Invoice::latest('id')->first();
        $nextNumber = $lastInvoice ? (int)substr($lastInvoice->invoice_number, 4) + 1 : 1;
        $invoiceNumber = 'INV-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'client_id' => $this->client_id,
            'company_bank_account_id' => $this->company_bank_account_id,
            'booking_id' => $this->booking_id ?: null,
            'created_by' => auth()->id(),
            'status' => 'draft',
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'description' => $this->description,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'amount_paid' => 0,
            'amount_due' => $this->total,
            'notes' => $this->notes,
        ]);

        // Create line items
        foreach ($this->lineItems as $item) {
            $invoice->lineItems()->create([
                'vehicle_id' => $item['vehicle_id'] ?: null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'unit' => $item['unit'],
            ]);
        }

        $this->dispatch('notify',
            type: 'success',
            message: 'Invoice created successfully!'
        );

        return redirect()->route('invoices.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Create New Invoice</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Invoice Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Load from Completed Booking (Optional)</flux:label>
                    <flux:select wire:model.live="booking_id" wire:change="loadBookingDetails">
                        <option value="">Select a completed booking...</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">
                                {{ $booking->booking_number }} - {{ $booking->client->name }}
                                ({{ $booking->start_date->format('d M Y') }})
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:description>Auto-populate invoice from a completed booking</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Client *</flux:label>
                    <flux:select wire:model.live="client_id">
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
                    <flux:description>Select the client for this invoice</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Invoice Date *</flux:label>
                    <flux:input wire:model="invoice_date" type="date" />
                    <flux:error name="invoice_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Due Date *</flux:label>
                    <flux:input wire:model="due_date" type="date" />
                    <flux:error name="due_date" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Bank Account</flux:label>
                    <flux:select wire:model="company_bank_account_id">
                        <option value="">Use Primary Account</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->bank_name }} - {{ $account->account_number }}
                                @if($account->is_primary) (Primary) @endif
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="company_bank_account_id" />
                    <flux:description>Select which bank account details to display on this invoice</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="2" placeholder="Brief description of the invoice..." />
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
                    <div class="flex justify-between text-sm text-red-600">
                        <span>Amount Due:</span>
                        <span class="font-medium">N${{  number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Internal Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Internal notes (not visible to client)..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Invoice
            </flux:button>

            <flux:button wire:navigate href="{{ route('invoices.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
