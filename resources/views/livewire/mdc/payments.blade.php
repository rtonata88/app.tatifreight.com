<?php

use App\Models\MdcPayment;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        $payments = MdcPayment::with(['createdBy', 'expense'])
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return [
            'payments' => $payments,
            'totalPaid' => MdcPayment::sum('amount'),
        ];
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">MDC Payment History</flux:heading>
        <flux:subheading>Payments made to RFANAM</flux:subheading>
        <flux:spacer />
        <flux:button href="{{ route('mdc.index') }}" variant="ghost" icon="arrow-left" wire:navigate>Back to MDC Charges</flux:button>
    </flux:header>

    <flux:separator />

    {{-- Summary Card --}}
    <div class="mt-6">
        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Payments Made</p>
                    <p class="text-3xl font-bold text-green-700">N${{ number_format($totalPaid, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">All-time payments to RFANAM</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Payments Table --}}
    <div class="mt-6">
        <flux:card>
            <flux:table>
                <flux:columns>
                    <flux:column>Payment Date</flux:column>
                    <flux:column>Reference</flux:column>
                    <flux:column>Amount</flux:column>
                    <flux:column>Payment Method</flux:column>
                    <flux:column>Bank Reference</flux:column>
                    <flux:column>Recorded By</flux:column>
                    <flux:column>Receipt</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($payments as $payment)
                        <flux:row :key="$payment->id">
                            <flux:cell>
                                <div class="text-sm">
                                    {{ $payment->payment_date->format('d M Y') }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="font-medium text-sm">
                                    {{ $payment->payment_reference }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="font-bold text-green-700">
                                    N${{ number_format($payment->amount, 2) }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm capitalize">
                                    {{ str_replace('_', ' ', $payment->payment_method) }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    {{ $payment->bank_reference ?? '-' }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    {{ $payment->createdBy->name ?? 'N/A' }}
                                    <div class="text-xs text-gray-500">
                                        {{ $payment->created_at->format('d M Y H:i') }}
                                    </div>
                                </div>
                            </flux:cell>

                            <flux:cell>
                                @if($payment->receipt_path)
                                    <a href="{{ route('mdc.payment.receipt', $payment->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 text-sm underline"
                                       target="_blank">
                                        Download
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                No payments recorded yet.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        </flux:card>
    </div>
</div>

