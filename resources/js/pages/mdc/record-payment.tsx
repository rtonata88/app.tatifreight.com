import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import { FileInput } from '@/components/file-input';
import { FormField } from '@/components/form-field';
import { Notice } from '@/components/notice';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { index, recordPayment } from '@/routes/mdc';
import { store } from '@/routes/mdc/record-payment';
import type { BreadcrumbItem, Option } from '@/types';

type UnpaidCalculation = {
    id: number;
    logbook_reference: string;
    vehicle_reg: string;
    date: string;
    mdc_amount: number;
    amount_paid: number;
    outstanding: number;
};

type Props = {
    unpaidCalculations: UnpaidCalculation[];
    totalUnpaid: number;
    defaultPaymentDate: string;
    paymentMethods: Option[];
};

type FormValues = {
    payment_date: string;
    amount: string;
    payment_method: string;
    bank_reference: string;
    receipt: File | null;
    notes: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'MDC charges', href: index() },
    { title: 'Record payment', href: recordPayment() },
];

export default function MdcRecordPayment({ unpaidCalculations, totalUnpaid, defaultPaymentDate, paymentMethods }: Props) {
    const form = useForm<FormValues>({
        payment_date: defaultPaymentDate,
        amount: '',
        payment_method: 'bank_transfer',
        bank_reference: '',
        receipt: null,
        notes: '',
    });
    const { data, setData, processing } = form;
    // payment_error is set by the server when saving fails after validation.
    const errors = form.errors as Partial<Record<keyof FormValues | 'payment_error', string>>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store().url, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Record MDC payment" />
            <PageContainer>
                <PageHeader
                    title="Record MDC payment"
                    description="Record payment made to RFANAM (Road Fund Administration)."
                    actions={
                        <Button asChild variant="ghost">
                            <Link href={index()}>
                                <ArrowLeft /> Back
                            </Link>
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {errors.payment_error && (
                            <Notice tone="error" title="Payment error">
                                {errors.payment_error}
                            </Notice>
                        )}

                        <form onSubmit={submit}>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Payment details</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <FormField label="Payment date" required htmlFor="payment_date" error={errors.payment_date} description="Date payment was made to RFANAM">
                                            <Input
                                                id="payment_date"
                                                type="date"
                                                value={data.payment_date}
                                                onChange={(e) => setData('payment_date', e.target.value)}
                                                aria-invalid={!!errors.payment_date}
                                            />
                                        </FormField>
                                        <FormField
                                            label="Amount (N$)"
                                            required
                                            htmlFor="amount"
                                            error={errors.amount}
                                            description={`Total outstanding: ${formatMoney(totalUnpaid)}`}
                                        >
                                            <Input
                                                id="amount"
                                                type="number"
                                                inputMode="decimal"
                                                step="0.01"
                                                placeholder="0.00"
                                                value={data.amount}
                                                onChange={(e) => setData('amount', e.target.value)}
                                                aria-invalid={!!errors.amount}
                                            />
                                            {totalUnpaid > 0 && (
                                                <Button
                                                    type="button"
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto justify-self-start p-0 md:h-auto"
                                                    onClick={() => setData('amount', totalUnpaid.toFixed(2))}
                                                >
                                                    Pay full amount
                                                </Button>
                                            )}
                                        </FormField>
                                        <FormField label="Payment method" required htmlFor="payment_method" error={errors.payment_method}>
                                            <NativeSelect
                                                id="payment_method"
                                                value={data.payment_method}
                                                onChange={(e) => setData('payment_method', e.target.value)}
                                                aria-invalid={!!errors.payment_method}
                                            >
                                                {paymentMethods.map((method) => (
                                                    <option key={method.value} value={method.value}>
                                                        {method.label}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </FormField>
                                        <FormField label="Bank reference / transaction ID" htmlFor="bank_reference" error={errors.bank_reference}>
                                            <Input
                                                id="bank_reference"
                                                placeholder="e.g., TXN-123456"
                                                value={data.bank_reference}
                                                onChange={(e) => setData('bank_reference', e.target.value)}
                                                aria-invalid={!!errors.bank_reference}
                                            />
                                        </FormField>
                                    </div>

                                    <FormField label="Receipt / proof of payment" htmlFor="receipt" error={errors.receipt} description="Upload receipt (PDF, JPG, PNG - max 5MB)">
                                        <FileInput
                                            id="receipt"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            file={data.receipt}
                                            onChange={(file) => setData('receipt', file)}
                                            aria-invalid={!!errors.receipt}
                                        />
                                    </FormField>

                                    <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                                        <Textarea
                                            id="notes"
                                            rows={3}
                                            placeholder="Additional notes about this payment..."
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                        />
                                    </FormField>

                                    <div className="flex justify-end gap-3">
                                        <Button asChild variant="ghost">
                                            <Link href={index()}>Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner />}
                                            Record payment
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </form>
                    </div>

                    {/* Phones: what is owed comes before the form. */}
                    <div className="order-first lg:order-none">
                        <Card>
                            <CardHeader>
                                <CardTitle>Unpaid MDC</CardTitle>
                                <CardDescription>Payment will be allocated to oldest charges first</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="rounded-lg border border-warning bg-(--nx-warn-wash) p-4">
                                    <div className="text-sm font-medium text-warning">Total outstanding</div>
                                    <div className="font-condensed text-2xl font-bold tabular-nums">{formatMoney(totalUnpaid)}</div>
                                </div>

                                {unpaidCalculations.length > 0 ? (
                                    <div className="space-y-2">
                                        <div className="text-sm font-medium">Recent unpaid charges ({unpaidCalculations.length})</div>
                                        <div className="max-h-96 space-y-2 overflow-y-auto">
                                            {unpaidCalculations.slice(0, 10).map((calc) => (
                                                <div key={calc.id} className="rounded-lg bg-muted p-3 text-xs">
                                                    <div className="mb-1 flex items-start justify-between">
                                                        <span className="font-mono font-medium">{calc.logbook_reference}</span>
                                                        <span className="font-mono font-bold tabular-nums">{formatMoney(calc.outstanding)}</span>
                                                    </div>
                                                    <div className="text-muted-foreground">
                                                        {calc.vehicle_reg} • {calc.date}
                                                    </div>
                                                    {calc.amount_paid > 0 && (
                                                        <div className="mt-1 text-warning">Partially paid: {formatMoney(calc.amount_paid)}</div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="p-4 text-center text-muted-foreground">
                                        <div>All MDC charges are paid.</div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </PageContainer>
        </AppLayout>
    );
}
