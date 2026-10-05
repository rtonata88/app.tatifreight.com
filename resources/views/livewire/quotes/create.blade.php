<?php

use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\Client;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

    #[Validate('nullable|exists:company_bank_accounts,id')]
    public $company_bank_account_id = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('required|date')]
    public $valid_until = '';

    #[Validate('nullable|string')]
    public $terms_conditions = '';

    #[Validate('nullable|string')]
    public $notes = '';

    public $lineItems = [];
    public $subtotal = 0;
    public $tax_amount = 0;
    public $total = 0;
    public $tax_rate = 15; // South African VAT

    public function mount()
    {
        // Default valid until 30 days from now
        $this->valid_until = now()->addDays(30)->format('Y-m-d');

        // Set default bank account to primary
        $primaryAccount = \App\Models\CompanyBankAccount::primary();
        if ($primaryAccount) {
            $this->company_bank_account_id = $primaryAccount->id;
        }

        // Add one empty line item to start
        $this->addLineItem();

        // Default terms and conditions
        $this->terms_conditions = "1. Quote valid for 30 days from issue date\n2. Payment terms as per agreement\n3. Prices subject to change based on fuel levy\n4. Booking confirmation required 24 hours in advance";
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')
                ->whereIn('status', ['available', 'in_use', 'maintenance'])
                ->orderBy('reg_number')
                ->get(),
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
            'unit' => 'trip', // trip, day, hour, km, tonne, etc.
        ];
    }

    public function removeLineItem($index)
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
        $this->calculateTotals();
    }

    public function updatedLineItems()
    {
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
                // Set description based on vehicle
                $this->lineItems[$index]['description'] = $vehicle->vehicleType->name . ' - ' . $vehicle->reg_number;

                // Default to daily rate if available
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

        // Generate quote number
        $lastQuote = Quote::latest('id')->first();
        $nextNumber = $lastQuote ? (int)substr($lastQuote->quote_number, 3) + 1 : 1;
        $quoteNumber = 'QT-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Create quote
        $quote = Quote::create([
            'quote_number' => $quoteNumber,
            'client_id' => $this->client_id,
            'company_bank_account_id' => $this->company_bank_account_id,
            'created_by' => auth()->id(),
            'version' => 1,
            'status' => 'draft',
            'valid_until' => $this->valid_until,
            'description' => $this->description,
            'terms_conditions' => $this->terms_conditions,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'notes' => $this->notes,
        ]);

        // Create line items
        foreach ($this->lineItems as $item) {
            $quote->lineItems()->create([
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
            message: 'Quote created successfully!'
        );

        return redirect()->route('quotes.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Create New Quote</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Quote Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <flux:description>Select the client for this quote</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Valid Until *</flux:label>
                    <flux:input wire:model="valid_until" type="date" />
                    <flux:error name="valid_until" />
                    <flux:description>Quote expiry date</flux:description>
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
                    <flux:description>Select which bank account details to display on this quote</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="2" placeholder="Brief description of the quote..." />
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

            {{-- Mobile Card View --}}
            <div class="mt-6 md:hidden space-y-4">
                @forelse($lineItems as $index => $item)
                    <div wire:key="card-{{ $index }}" class="bg-white border border-gray-300 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-gray-500">LINE ITEM #{{ $index + 1 }}</span>
                            @if(count($lineItems) > 1)
                                <button
                                    type="button"
                                    wire:click="removeLineItem({{ $index }})"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Vehicle *</label>
                                <select 
                                    wire:model.live="lineItems.{{ $index }}.vehicle_id" 
                                    wire:change="loadVehicleRate({{ $index }})"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Select vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">
                                            {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("lineItems.{$index}.vehicle_id") 
                                    <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Description *</label>
                                <input 
                                    wire:model="lineItems.{{ $index }}.description"
                                    type="text"
                                    placeholder="Item description"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                                @error("lineItems.{$index}.description") 
                                    <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Unit</label>
                                    <select 
                                        wire:model.live="lineItems.{{ $index }}.unit"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
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
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Quantity *</label>
                                    <input 
                                        wire:model.blur="lineItems.{{ $index }}.quantity"
                                        wire:change="calculateLineAmount({{ $index }})"
                                        type="number"
                                        min="1"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Unit Price *</label>
                                    <input 
                                        wire:model.blur="lineItems.{{ $index }}.unit_price"
                                        wire:change="calculateLineAmount({{ $index }})"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
                                    <input 
                                        wire:model="lineItems.{{ $index }}.amount"
                                        type="text"
                                        readonly
                                        class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50 font-semibold"
                                        value="N${{ number_format($item['amount'] ?? 0, 2) }}"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-gray-50 border border-gray-300 rounded-lg p-8 text-center text-gray-500">
                        No line items added yet. Click "Add Line" to get started.
                    </div>
                @endforelse
            </div>

            {{-- Desktop Excel-style Table --}}
            <div class="mt-6 overflow-x-auto hidden md:block">
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
                    <flux:textarea wire:model="notes" rows="3" placeholder="Internal notes (not visible to client)..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Quote
            </flux:button>

            <flux:button wire:navigate href="{{ route('quotes.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
