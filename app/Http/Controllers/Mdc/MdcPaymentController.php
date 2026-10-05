<?php

namespace App\Http\Controllers\Mdc;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\MdcCalculation;
use App\Models\MdcPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/mdc/{record-payment,payments}.
 */
class MdcPaymentController extends Controller
{
    public function index(): Response
    {
        $payments = MdcPayment::with(['createdBy', 'expense'])
            ->orderBy('payment_date', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (MdcPayment $payment) => [
                'id' => $payment->id,
                'payment_date' => $payment->payment_date?->format('Y-m-d'),
                'payment_reference' => $payment->payment_reference,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'bank_reference' => $payment->bank_reference,
                'created_by' => $payment->createdBy?->name,
                'created_at' => $payment->created_at?->format('Y-m-d\TH:i:s'),
                'receipt_url' => $payment->receipt_path ? route('mdc.payment.receipt', $payment->id) : null,
            ]);

        return Inertia::render('mdc/payments', [
            'payments' => $payments,
            'totalPaid' => (float) MdcPayment::sum('amount'),
        ]);
    }

    public function create(): Response
    {
        $unpaid = MdcCalculation::with(['logbook', 'vehicle'])
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('calculation_date', 'asc')
            ->get()
            ->map(fn (MdcCalculation $calc) => [
                'id' => $calc->id,
                'logbook_reference' => $calc->logbook ? $calc->logbook->date->format('d M Y') : 'N/A',
                'vehicle_reg' => $calc->vehicle->reg_number ?? 'N/A',
                'date' => $calc->calculation_date->format('Y-m-d'),
                'mdc_amount' => (float) $calc->mdc_amount,
                'amount_paid' => (float) $calc->amount_paid,
                'outstanding' => $calc->outstanding_amount,
            ]);

        return Inertia::render('mdc/record-payment', [
            'unpaidCalculations' => $unpaid->values(),
            'totalUnpaid' => (float) $unpaid->sum('outstanding'),
            'defaultPaymentDate' => now()->format('Y-m-d'),
            'paymentMethods' => [
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'eft', 'label' => 'EFT'],
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'cheque', 'label' => 'Cheque'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,cash,eft,cheque',
            'bank_reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $paymentReference = MdcPayment::generatePaymentReference();

            // Receipt is stored privately in storage/app/private.
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('mdc-receipts', 'local');
            }

            // Create expense first (MDC payment to RFANAM), auto-approved.
            $expense = Expense::create([
                'category' => 'mdc_payment',
                'amount' => $validated['amount'],
                'expense_date' => $validated['payment_date'],
                'description' => "MDC Payment to RFANAM - {$paymentReference}",
                'receipt_path' => $receiptPath,
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'notes' => $validated['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            $mdcPayment = MdcPayment::create([
                'payment_reference' => $paymentReference,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_reference' => $validated['bank_reference'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'expense_id' => $expense->id,
                'created_by' => $request->user()->id,
            ]);

            // Allocate payment to unpaid calculations (oldest first).
            $remainingAmount = (float) $validated['amount'];
            $calculations = MdcCalculation::whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->orderBy('calculation_date', 'asc')
                ->get();

            foreach ($calculations as $calc) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $outstanding = $calc->outstanding_amount;
                $amountToAllocate = min($remainingAmount, $outstanding);

                $mdcPayment->mdcCalculations()->attach($calc->id, [
                    'amount_allocated' => $amountToAllocate,
                ]);

                $newAmountPaid = $calc->amount_paid + $amountToAllocate;
                $calc->update([
                    'amount_paid' => $newAmountPaid,
                    'payment_status' => $newAmountPaid >= $calc->mdc_amount ? 'paid' : 'partially_paid',
                ]);

                $remainingAmount -= $amountToAllocate;
            }

            DB::commit();

            return redirect()->route('mdc.index')
                ->with('success', "MDC Payment recorded successfully! Reference: {$paymentReference}");
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('MDC Payment failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['payment_error' => 'Failed to record payment: '.$e->getMessage()])
                ->with('error', 'Failed to record payment. Please check the form for errors.');
        }
    }
}
