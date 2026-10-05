import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/format';
import { store as storePayment } from '@/routes/invoices/payments';

export const PAYMENT_METHODS = [
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
    { value: 'eft', label: 'EFT' },
    { value: 'card', label: 'Credit/Debit Card' },
];

type Props = {
    invoiceId: number;
    amountDue: number;
    today: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type Values = {
    paymentAmount: string | number;
    paymentDate: string;
    paymentMethod: string;
    transactionReference: string;
    paymentNotes: string;
};

/** The old $showPaymentModal / openPaymentModal() / recordPayment() modal. */
export function RecordPaymentDialog({ invoiceId, amountDue, today, open, onOpenChange }: Props) {
    const form = useForm<Values>({
        paymentAmount: amountDue,
        paymentDate: today,
        paymentMethod: 'bank_transfer',
        transactionReference: '',
        paymentNotes: '',
    });
    const { data, setData, errors, processing } = form;

    /** closePaymentModal() resets the fields; openPaymentModal() defaults the amount to the balance. */
    const changeOpen = (next: boolean) => {
        if (next) {
            form.clearErrors();
            form.setData({ paymentAmount: amountDue, paymentDate: today, paymentMethod: 'bank_transfer', transactionReference: '', paymentNotes: '' });
        }
        onOpenChange(next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(storePayment(invoiceId).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-6">
                    <DialogHeader>
                        <DialogTitle>Record Payment</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <FormField label="Payment Amount (R)" required htmlFor="paymentAmount" error={errors.paymentAmount} description={`Maximum: ${formatMoney(amountDue, 'N$')}`}>
                            <Input
                                id="paymentAmount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={amountDue}
                                value={data.paymentAmount}
                                onChange={(e) => setData('paymentAmount', e.target.value)}
                                aria-invalid={!!errors.paymentAmount}
                            />
                        </FormField>

                        <FormField label="Payment Date" required htmlFor="paymentDate" error={errors.paymentDate}>
                            <Input id="paymentDate" type="date" value={data.paymentDate} onChange={(e) => setData('paymentDate', e.target.value)} aria-invalid={!!errors.paymentDate} />
                        </FormField>

                        <FormField label="Payment Method" required htmlFor="paymentMethod" error={errors.paymentMethod}>
                            <NativeSelect id="paymentMethod" value={data.paymentMethod} onChange={(e) => setData('paymentMethod', e.target.value)}>
                                {PAYMENT_METHODS.map((method) => (
                                    <option key={method.value} value={method.value}>
                                        {method.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>

                        <FormField label="Transaction Reference" htmlFor="transactionReference" error={errors.transactionReference}>
                            <Input
                                id="transactionReference"
                                value={data.transactionReference}
                                onChange={(e) => setData('transactionReference', e.target.value)}
                                placeholder="Bank reference or transaction ID"
                            />
                        </FormField>

                        <FormField label="Notes" htmlFor="paymentNotes" error={errors.paymentNotes}>
                            <Textarea id="paymentNotes" rows={2} value={data.paymentNotes} onChange={(e) => setData('paymentNotes', e.target.value)} />
                        </FormField>
                    </div>

                    <DialogFooter className="gap-3 sm:justify-start">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Record Payment
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => changeOpen(false)}>
                            Cancel
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
