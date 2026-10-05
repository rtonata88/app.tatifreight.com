<?php

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    public Invoice $invoice;

    #[Validate('required|exists:clients,id')]
    public $client_id;

    #[Validate('nullable|exists:company_bank_accounts,id')]
    public $company_bank_account_id;

    #[Validate('required|date')]
    public $invoice_date;

    #[Validate('required|date')]
    public $due_date;

    #[Validate('nullable|string')]
    public $description;

    #[Validate('nullable|string')]
    public $notes;

    #[Validate('required|in:draft,sent,unpaid,partial,paid,overdue')]
    public $status;

    public $lineItems = [];
    public $subtotal = 0;
    public $tax_amount = 0;
    public $total = 0;
    public $tax_rate = 15;

    // Payment tracking
    public $showPaymentModal = false;
    public $paymentAmount = 0;
    public $paymentDate = '';
    public $paymentMethod = 'bank_transfer';
    public $transactionReference = '';
    public $paymentNotes = '';

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->client_id = $this->invoice->client_id;
        $this->company_bank_account_id = $this->invoice->company_bank_account_id;
        $this->invoice_date = $this->invoice->invoice_date?->format('Y-m-d');
        $this->due_date = $this->invoice->due_date?->format('Y-m-d');
        $this->description = $this->invoice->description;
        $this->notes = $this->invoice->notes;
        $this->status = $this->invoice->status;
        $this->subtotal = (float)$this->invoice->subtotal;
        $this->tax_amount = (float)$this->invoice->tax_amount;
        $this->total = (float)$this->invoice->total;

        // Load line items
        foreach ($this->invoice->lineItems as $item) {
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

        // Default payment amount to remaining balance
        $this->paymentAmount = (float)$this->invoice->amount_due;
        $this->paymentDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get(),
            'payments' => $this->invoice->payments()->orderBy('payment_date', 'desc')->get(),
            'bankAccounts' => \App\Models\CompanyBankAccount::active(),
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
        if (isset($this->lineItems[$index]['id'])) {
            InvoiceLineItem::find($this->lineItems[$index]['id'])->delete();
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

        // Recalculate amount_due
        $totalPaid = $this->invoice->payments()->sum('amount');
        $amountDue = $this->total - $totalPaid;

        // Update invoice
        $this->invoice->update([
            'client_id' => $this->client_id,
            'company_bank_account_id' => $this->company_bank_account_id,
            'status' => $this->status,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'description' => $this->description,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'amount_paid' => $totalPaid,
            'amount_due' => $amountDue,
            'notes' => $this->notes,
        ]);

        // Update or create line items
        foreach ($this->lineItems as $item) {
            if ($item['id']) {
                InvoiceLineItem::find($item['id'])->update([
                    'vehicle_id' => $item['vehicle_id'] ?: null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'unit' => $item['unit'],
                ]);
            } else {
                $this->invoice->lineItems()->create([
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
            message: 'Invoice updated successfully!'
        );

        return redirect()->route('invoices.index');
    }

    public function openPaymentModal()
    {
        $this->showPaymentModal = true;
        $this->paymentAmount = (float)$this->invoice->amount_due;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentAmount = 0;
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentMethod = 'bank_transfer';
        $this->transactionReference = '';
        $this->paymentNotes = '';
    }

    public function recordPayment()
    {
        $validated = $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:' . $this->invoice->amount_due,
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string',
            'transactionReference' => 'nullable|string|max:255',
            'paymentNotes' => 'nullable|string',
        ]);

        // Generate payment reference
        $lastPayment = Payment::latest('id')->first();
        $nextNumber = $lastPayment ? (int)substr($lastPayment->payment_reference, 4) + 1 : 1;
        $paymentReference = 'PAY-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Create payment
        Payment::create([
            'payment_reference' => $paymentReference,
            'invoice_id' => $this->invoice->id,
            'client_id' => $this->invoice->client_id,
            'amount' => $this->paymentAmount,
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'transaction_reference' => $this->transactionReference,
            'notes' => $this->paymentNotes,
        ]);

        // Update invoice amounts
        $totalPaid = $this->invoice->payments()->sum('amount') + $this->paymentAmount;
        $amountDue = $this->invoice->total - $totalPaid;

        // Determine new status
        $newStatus = 'paid';
        if ($amountDue > 0) {
            $newStatus = 'partial';
        }

        $this->invoice->update([
            'amount_paid' => $totalPaid,
            'amount_due' => $amountDue,
            'status' => $newStatus,
            'paid_at' => $amountDue <= 0 ? now() : null,
        ]);

        $this->dispatch('notify',
            type: 'success',
            message: 'Payment recorded successfully!'
        );

        $this->closePaymentModal();

        // Refresh the invoice
        $this->mount($this->invoice->id);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Invoice: {{ $invoice->invoice_number }}</flux:heading>

        <div class="flex items-center gap-2">
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
            >
                {{ ucfirst($invoice->status) }}
            </flux:badge>

            @if($invoice->amount_due > 0)
                <flux:button
                    wire:click="openPaymentModal"
                    variant="primary"
                    icon="currency-dollar"
                >
                    Record Payment
                </flux:button>
            @endif
        </div>
    </flux:header>

    {{-- Payment Summary Card --}}
    <flux:card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-gray-600">Total Amount</p>
                <p class="text-xl font-bold text-gray-900">N${{  number_format($invoice->total, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Amount Paid</p>
                <p class="text-xl font-bold text-green-600">N${{  number_format($invoice->amount_paid, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Amount Due</p>
                <p class="text-xl font-bold text-red-600">N${{  number_format($invoice->amount_due, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Due Date</p>
                <p class="text-lg font-medium {{ $invoice->due_date && $invoice->due_date->isPast() && $invoice->amount_due > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $invoice->due_date?->format('d M Y') }}
                    @if($invoice->due_date && $invoice->due_date->isPast() && $invoice->amount_due > 0)
                        <span class="text-xs">(Overdue)</span>
                    @endif
                </p>
            </div>
        </div>
    </flux:card>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Invoice Details</flux:heading>

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
                    <flux:label>Invoice Date *</flux:label>
                    <flux:input wire:model="invoice_date" type="date" />
                    <flux:error name="invoice_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Due Date *</flux:label>
                    <flux:input wire:model="due_date" type="date" />
                    <flux:error name="due_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </flux:select>
                    <flux:error name="status" />
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

        {{-- Payment History --}}
        @if($payments->count() > 0)
            <flux:card>
                <flux:heading size="lg">Payment History</flux:heading>

                <div class="mt-6">
                    <flux:table>
                        <flux:columns>
                            <flux:column>Payment Ref</flux:column>
                            <flux:column>Date</flux:column>
                            <flux:column>Amount</flux:column>
                            <flux:column>Method</flux:column>
                            <flux:column>Transaction Ref</flux:column>
                        </flux:columns>

                        <flux:rows>
                            @foreach($payments as $payment)
                                <flux:row :key="$payment->id">
                                    <flux:cell>{{ $payment->payment_reference }}</flux:cell>
                                    <flux:cell>{{ $payment->payment_date->format('d M Y') }}</flux:cell>
                                    <flux:cell class="font-medium text-green-600">N${{  number_format($payment->amount, 2) }}</flux:cell>
                                    <flux:cell>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</flux:cell>
                                    <flux:cell>{{ $payment->transaction_reference ?: '-' }}</flux:cell>
                                </flux:row>
                            @endforeach
                        </flux:rows>
                    </flux:table>
                </div>
            </flux:card>
        @endif

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

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Invoice
            </flux:button>

            <flux:button wire:navigate href="{{ route('invoices.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>

    {{-- Payment Modal --}}
    @if($showPaymentModal)
        <flux:modal wire:model="showPaymentModal">
            <div class="p-6">
                <flux:heading size="lg" class="mb-6">Record Payment</flux:heading>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Payment Amount (R) *</flux:label>
                        <flux:input wire:model="paymentAmount" type="number" step="0.01" min="0.01" :max="$invoice->amount_due" />
                        <flux:error name="paymentAmount" />
                        <flux:description>Maximum: N${{  number_format($invoice->amount_due, 2) }}</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Payment Date *</flux:label>
                        <flux:input wire:model="paymentDate" type="date" />
                        <flux:error name="paymentDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Payment Method *</flux:label>
                        <flux:select wire:model="paymentMethod">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="eft">EFT</option>
                            <option value="card">Credit/Debit Card</option>
                        </flux:select>
                        <flux:error name="paymentMethod" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Transaction Reference</flux:label>
                        <flux:input wire:model="transactionReference" placeholder="Bank reference or transaction ID" />
                        <flux:error name="transactionReference" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea wire:model="paymentNotes" rows="2" />
                        <flux:error name="paymentNotes" />
                    </flux:field>
                </div>

                <div class="mt-6 flex gap-3">
                    <flux:button wire:click="recordPayment" variant="primary">
                        Record Payment
                    </flux:button>
                    <flux:button wire:click="closePaymentModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
