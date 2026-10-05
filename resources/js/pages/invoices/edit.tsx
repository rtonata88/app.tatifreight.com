import { Head } from '@inertiajs/react';
import { CircleDollarSign } from 'lucide-react';
import { useState } from 'react';
import { InvoiceForm, type BankAccountOption, type ClientOption } from '@/components/invoices/invoice-form';
import type { InvoiceLineItem } from '@/components/invoices/invoice-line-items';
import { invoiceStatusTone, statusLabel } from '@/components/invoices/invoice-status';
import { RecordPaymentDialog } from '@/components/invoices/record-payment-dialog';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney, humanize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { edit, index } from '@/routes/invoices';
import type { BreadcrumbItem, Option } from '@/types';

type PaymentRow = {
    id: number;
    payment_reference: string;
    payment_date: string | null;
    amount: number;
    payment_method: string;
    transaction_reference: string | null;
};

type Props = {
    invoice: {
        id: number;
        invoice_number: string;
        client_id: number;
        company_bank_account_id: number | null;
        invoice_date: string | null;
        due_date: string | null;
        description: string | null;
        notes: string | null;
        status: string;
        total: number;
        amount_paid: number;
        amount_due: number;
        is_overdue: boolean;
        line_items: (InvoiceLineItem & { id: number })[];
    };
    payments: PaymentRow[];
    clients: ClientOption[];
    vehicles: Option[];
    bankAccounts: BankAccountOption[];
    today: string;
    taxRate: number;
};

export default function InvoicesEdit({ invoice, payments, clients, vehicles, bankAccounts, today, taxRate }: Props) {
    const [paymentOpen, setPaymentOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Invoices', href: index() },
        { title: invoice.invoice_number, href: edit(invoice.id) },
    ];

    const title = `Edit invoice: ${invoice.invoice_number}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <PageContainer>
                <PageHeader
                    title={title}
                    actions={
                        <>
                            <StatusBadge tone={invoiceStatusTone[invoice.status] ?? 'gray'}>{statusLabel(invoice.status)}</StatusBadge>
                            {invoice.amount_due > 0 && (
                                <Button onClick={() => setPaymentOpen(true)}>
                                    <CircleDollarSign /> Record payment
                                </Button>
                            )}
                        </>
                    }
                />

                {/* Payment summary */}
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">
                            <div>
                                <p className="text-sm text-muted-foreground">Total amount</p>
                                <p className="font-condensed text-xl font-bold tabular-nums">{formatMoney(invoice.total, 'N$')}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Amount paid</p>
                                <p className="font-condensed text-xl font-bold tabular-nums text-success">{formatMoney(invoice.amount_paid, 'N$')}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Amount due</p>
                                <p className="font-condensed text-xl font-bold tabular-nums text-destructive">{formatMoney(invoice.amount_due, 'N$')}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Due date</p>
                                <p className={cn('text-lg font-medium', invoice.is_overdue && 'text-destructive')}>
                                    <span className="font-mono">{formatDate(invoice.due_date, '')}</span>
                                    {invoice.is_overdue && <span className="text-xs"> (Overdue)</span>}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <InvoiceForm
                    // Re-mount after a payment so the form picks up the refreshed invoice (as mount() did).
                    key={`${invoice.id}-${invoice.amount_paid}-${invoice.status}`}
                    invoiceId={invoice.id}
                    clients={clients}
                    vehicles={vehicles}
                    bankAccounts={bankAccounts}
                    taxRate={taxRate}
                    today={today}
                    initial={{
                        client_id: invoice.client_id,
                        company_bank_account_id: invoice.company_bank_account_id ?? '',
                        invoice_date: invoice.invoice_date ?? '',
                        due_date: invoice.due_date ?? '',
                        status: invoice.status,
                        description: invoice.description ?? '',
                        notes: invoice.notes ?? '',
                        items: invoice.line_items,
                    }}
                    beforeNotes={payments.length > 0 && <PaymentHistory payments={payments} />}
                />

                <RecordPaymentDialog invoiceId={invoice.id} amountDue={invoice.amount_due} today={today} open={paymentOpen} onOpenChange={setPaymentOpen} />
            </PageContainer>
        </AppLayout>
    );
}

function PaymentHistory({ payments }: { payments: PaymentRow[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Payment history</CardTitle>
            </CardHeader>
            <CardContent>
                {/* Phones: cards */}
                <div className="space-y-3 md:hidden">
                    {payments.map((payment) => (
                        <div key={payment.id} className="space-y-2 rounded-lg border p-4 text-sm">
                            <div className="flex items-center justify-between gap-2">
                                <span className="font-mono font-semibold">{payment.payment_reference}</span>
                                <span className="font-mono font-medium tabular-nums text-success">{formatMoney(payment.amount, 'N$')}</span>
                            </div>
                            <div className="text-muted-foreground">
                                {formatDate(payment.payment_date)} · {humanize(payment.payment_method)}
                            </div>
                            <div className="text-muted-foreground">Ref: {payment.transaction_reference || '-'}</div>
                        </div>
                    ))}
                </div>

                <div className="hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Payment ref</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead>Transaction ref</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payments.map((payment) => (
                                <TableRow key={payment.id}>
                                    <TableCell className="font-mono">{payment.payment_reference}</TableCell>
                                    <TableCell className="font-mono">{formatDate(payment.payment_date)}</TableCell>
                                    <TableCell className="text-right font-mono font-medium tabular-nums text-success">{formatMoney(payment.amount, 'N$')}</TableCell>
                                    <TableCell>{humanize(payment.payment_method)}</TableCell>
                                    <TableCell className="font-mono">{payment.transaction_reference || '-'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
