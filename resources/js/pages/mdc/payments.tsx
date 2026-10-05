import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import { DataPagination } from '@/components/data-pagination';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import { index, payments as paymentsRoute } from '@/routes/mdc';
import type { BreadcrumbItem, Paginated } from '@/types';

type PaymentRow = {
    id: number;
    payment_date: string | null;
    payment_reference: string;
    amount: number;
    payment_method: string;
    bank_reference: string | null;
    created_by: string | null;
    created_at: string | null;
    receipt_url: string | null;
};

type Props = {
    payments: Paginated<PaymentRow>;
    totalPaid: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'MDC charges', href: index() },
    { title: 'Payment history', href: paymentsRoute() },
];

export default function MdcPayments({ payments, totalPaid }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC payment history" />
            <PageContainer>
                <PageHeader
                    title="MDC payment history"
                    description="Payments made to RFANAM."
                    actions={
                        <Button asChild variant="ghost">
                            <Link href={index()}>
                                <ArrowLeft /> Back to MDC charges
                            </Link>
                        </Button>
                    }
                />

                <StatCard label="Total payments made" value={formatMoney(totalPaid)} hint="All-time payments to RFANAM" tone="positive" />

                <Card>
                    <CardContent className="space-y-4">
                        {/* Phones: cards */}
                        <div className="space-y-3 md:hidden">
                            {payments.data.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">No payments recorded yet.</p>
                            ) : (
                                payments.data.map((payment) => (
                                    <div key={payment.id} className="space-y-3 rounded-lg border bg-card p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="truncate font-mono text-sm font-medium">{payment.payment_reference}</div>
                                                <div className="text-sm text-muted-foreground">{formatDate(payment.payment_date)}</div>
                                            </div>
                                            <div className="font-mono font-bold whitespace-nowrap text-success tabular-nums">
                                                {formatMoney(payment.amount)}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-x-4 gap-y-2 border-t pt-3">
                                            <div>
                                                <div className="text-xs text-muted-foreground">Payment method</div>
                                                <div className="text-sm capitalize">{payment.payment_method.replace(/_/g, ' ')}</div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">Bank reference</div>
                                                <div className="font-mono text-sm break-all">{payment.bank_reference ?? '-'}</div>
                                            </div>
                                            <div className="col-span-2">
                                                <div className="text-xs text-muted-foreground">Recorded by</div>
                                                <div className="text-sm">
                                                    {payment.created_by ?? 'N/A'}
                                                    <span className="text-xs text-muted-foreground"> · {formatDateTime(payment.created_at)}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {payment.receipt_url && (
                                            <Button asChild variant="outline" size="sm" className="w-full">
                                                <a href={payment.receipt_url} target="_blank" rel="noreferrer">
                                                    <Download /> Download receipt
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Tablets and up: table */}
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Payment date</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead>Payment method</TableHead>
                                        <TableHead>Bank reference</TableHead>
                                        <TableHead>Recorded by</TableHead>
                                        <TableHead>Receipt</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                                No payments recorded yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        payments.data.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell className="text-sm">{formatDate(payment.payment_date)}</TableCell>
                                                <TableCell className="font-mono text-sm font-medium">{payment.payment_reference}</TableCell>
                                                <TableCell className="text-right font-mono font-bold text-success tabular-nums">
                                                    {formatMoney(payment.amount)}
                                                </TableCell>
                                                <TableCell className="text-sm capitalize">{payment.payment_method.replace(/_/g, ' ')}</TableCell>
                                                <TableCell className="font-mono text-sm">{payment.bank_reference ?? '-'}</TableCell>
                                                <TableCell className="text-sm">
                                                    {payment.created_by ?? 'N/A'}
                                                    <div className="text-xs text-muted-foreground">{formatDateTime(payment.created_at)}</div>
                                                </TableCell>
                                                <TableCell>
                                                    {payment.receipt_url ? (
                                                        <a
                                                            href={payment.receipt_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="text-sm text-primary underline hover:text-primary/80"
                                                        >
                                                            Download
                                                        </a>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <DataPagination paginator={payments} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}
