<?php

use App\Models\MdcPayment;
use App\Models\MdcCalculation;
use App\Models\Expense;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    #[Validate('required|date')]
    public $payment_date = '';

    #[Validate('required|numeric|min:0.01')]
    public $amount = '';

    #[Validate('required|in:bank_transfer,cash,eft,cheque')]
    public $payment_method = 'bank_transfer';

    #[Validate('nullable|string|max:255')]
    public $bank_reference = '';

    #[Validate('nullable|file|mimes:pdf,jpg,jpeg,png|max:5120')]
    public $receipt = null;

    #[Validate('nullable|string')]
    public $notes = '';

    public $unpaidCalculations = [];
    public $totalUnpaid = 0;

    public function mount()
    {
        $this->payment_date = now()->format('Y-m-d');
        $this->loadUnpaidCalculations();
    }

    public function loadUnpaidCalculations()
    {
        $calculations = MdcCalculation::with(['logbook', 'vehicle'])
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('calculation_date', 'asc')
            ->get();

        $this->unpaidCalculations = $calculations->map(function($calc) {
            return [
                'id' => $calc->id,
                'logbook_reference' => $calc->logbook ? $calc->logbook->date->format('d M Y') : 'N/A',
                'vehicle_reg' => $calc->vehicle->reg_number ?? 'N/A',
                'date' => $calc->calculation_date->format('Y-m-d'),
                'mdc_amount' => $calc->mdc_amount,
                'amount_paid' => $calc->amount_paid,
                'outstanding' => $calc->outstanding_amount,
            ];
        })->toArray();

        $this->totalUnpaid = array_sum(array_column($this->unpaidCalculations, 'outstanding'));
    }

    public function save()
    {
        $this->validate();

        \DB::beginTransaction();
        try {
            // Generate payment reference
            $paymentReference = MdcPayment::generatePaymentReference();

            // Save receipt file if uploaded (stored privately in storage/app/private)
            $receiptPath = null;
            if ($this->receipt) {
                $receiptPath = $this->receipt->store('mdc-receipts', 'local');
            }

            // Create expense first (MDC payment to RFANAM)
            $expense = Expense::create([
                'category' => 'mdc_payment',
                'amount' => $this->amount,
                'expense_date' => $this->payment_date,
                'description' => "MDC Payment to RFANAM - {$paymentReference}",
                'receipt_path' => $receiptPath,
                'status' => 'approved', // Auto-approve MDC payments
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $this->notes,
                'user_id' => auth()->id(),
            ]);

            // Create MDC payment record
            $mdcPayment = MdcPayment::create([
                'payment_reference' => $paymentReference,
                'payment_date' => $this->payment_date,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'bank_reference' => $this->bank_reference,
                'receipt_path' => $receiptPath,
                'notes' => $this->notes,
                'expense_id' => $expense->id,
                'created_by' => auth()->id(),
            ]);

            // Allocate payment to unpaid calculations (oldest first)
            $remainingAmount = (float)$this->amount;
            $calculations = MdcCalculation::whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->orderBy('calculation_date', 'asc')
                ->get();

            foreach ($calculations as $calc) {
                if ($remainingAmount <= 0) break;

                $outstanding = $calc->outstanding_amount;
                $amountToAllocate = min($remainingAmount, $outstanding);

                // Attach payment to calculation with allocated amount
                $mdcPayment->mdcCalculations()->attach($calc->id, [
                    'amount_allocated' => $amountToAllocate
                ]);

                // Update calculation payment status
                $newAmountPaid = $calc->amount_paid + $amountToAllocate;
                $calc->update([
                    'amount_paid' => $newAmountPaid,
                    'payment_status' => $newAmountPaid >= $calc->mdc_amount ? 'paid' : 'partially_paid'
                ]);

                $remainingAmount -= $amountToAllocate;
            }

            \DB::commit();

            $this->dispatch('notify',
                type: 'success',
                message: "MDC Payment recorded successfully! Reference: {$paymentReference}"
            );

            return redirect()->route('mdc.index');

        } catch (\Exception $e) {
            \DB::rollBack();
            logger()->error('MDC Payment failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Add error to the form
            $this->addError('payment_error', 'Failed to record payment: ' . $e->getMessage());

            $this->dispatch('notify',
                type: 'error',
                message: 'Failed to record payment. Please check the form for errors.'
            );
        }
    }

    public function with(): array
    {
        return [
            'paymentMethods' => [
                'bank_transfer' => 'Bank Transfer',
                'eft' => 'EFT',
                'cash' => 'Cash',
                'cheque' => 'Cheque',
            ]
        ];
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Record MDC Payment</flux:heading>
        <flux:subheading>Record payment made to RFANAM (Road Fund Administration)</flux:subheading>
        <flux:spacer />
        <flux:button href="{{ route('mdc.index') }}" variant="ghost" icon="arrow-left" wire:navigate>Back</flux:button>
    </flux:header>

    <flux:separator />

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Payment Form --}}
        <div class="lg:col-span-2">
            {{-- Error Alert --}}
            @error('payment_error')
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">Payment Error</h3>
                            <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                        </div>
                    </div>
                </div>
            @enderror

            <form wire:submit="save">
                <flux:card>
                    <flux:heading size="lg">Payment Details</flux:heading>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Payment Date *</flux:label>
                            <flux:input wire:model="payment_date" type="date" />
                            <flux:error name="payment_date" />
                            <flux:description>Date payment was made to RFANAM</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Amount (N$) *</flux:label>
                            <flux:input wire:model.live="amount" type="number" step="0.01" placeholder="0.00" />
                            <flux:error name="amount" />
                            <flux:description>Total outstanding: N${{ number_format($totalUnpaid, 2) }}</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Payment Method *</flux:label>
                            <flux:select wire:model="payment_method">
                                @foreach($paymentMethods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="payment_method" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Bank Reference / Transaction ID</flux:label>
                            <flux:input wire:model="bank_reference" placeholder="e.g., TXN-123456" />
                            <flux:error name="bank_reference" />
                        </flux:field>
                    </div>

                    <div class="mt-6">
                        <flux:field>
                            <flux:label>Receipt / Proof of Payment</flux:label>
                            <flux:input wire:model="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png" />
                            <flux:error name="receipt" />
                            <flux:description>Upload receipt (PDF, JPG, PNG - max 5MB)</flux:description>
                        </flux:field>
                    </div>

                    <div class="mt-6">
                        <flux:field>
                            <flux:label>Notes</flux:label>
                            <flux:textarea wire:model="notes" rows="3" placeholder="Additional notes about this payment..." />
                            <flux:error name="notes" />
                        </flux:field>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <flux:button href="{{ route('mdc.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Record Payment</flux:button>
                    </div>
                </flux:card>
            </form>
        </div>

        {{-- Unpaid Calculations Summary --}}
        <div>
            <flux:card>
                <flux:heading size="lg">Unpaid MDC</flux:heading>
                <flux:description>Payment will be allocated to oldest charges first</flux:description>

                <div class="mt-6 space-y-4">
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="text-sm text-amber-800 dark:text-amber-200 font-medium">Total Outstanding</div>
                        <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">N${{ number_format($totalUnpaid, 2) }}</div>
                    </div>

                    @if(count($unpaidCalculations) > 0)
                        <div class="space-y-2">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Recent Unpaid Charges ({{ count($unpaidCalculations) }})
                            </div>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach(array_slice($unpaidCalculations, 0, 10) as $calc)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs">
                                        <div class="flex justify-between items-start mb-1">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $calc['logbook_reference'] }}</span>
                                            <span class="font-bold text-gray-900 dark:text-gray-100">N${{ number_format($calc['outstanding'], 2) }}</span>
                                        </div>
                                        <div class="text-gray-600 dark:text-gray-400">
                                            {{ $calc['vehicle_reg'] }} • {{ $calc['date'] }}
                                        </div>
                                        @if($calc['amount_paid'] > 0)
                                            <div class="mt-1 text-amber-600 dark:text-amber-400">
                                                Partially paid: N${{ number_format($calc['amount_paid'], 2) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-4xl mb-2">✓</div>
                            <div>All MDC charges are paid!</div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>
</div>

