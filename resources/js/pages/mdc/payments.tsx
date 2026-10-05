import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CircleCheck } from 'lucide-react';
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
    { title: 'MDC Charges', href: index() },
    { title: 'Payment History', href: paymentsRoute() },
];

export default function MdcPayments({ payments, totalPaid }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC Payment History" />
            <PageContainer>
                <PageHeader
                    title="MDC Payment History"
                    description="Payments made to RFANAM"
                    actions={
                        <Button asChild variant="ghost">
                            <Link href={index()}>
                                <ArrowLeft /> Back to MDC Charges
                            </Link>
                        </Button>
                    }
                />

                <StatCard
                    label="Total Payments Made"
                    value={formatMoney(totalPaid)}
                    hint="All-time payments to RFANAM"
                    icon={CircleCheck}
                    valueClassName="text-3xl text-green-700 dark:text-green-400"
                />

                <Card>
                    <CardContent className="space-y-4">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Payment Date</TableHead>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Payment Method</TableHead>
                                    <TableHead>Bank Reference</TableHead>
                                    <TableHead>Recorded By</TableHead>
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
                                            <TableCell className="text-sm font-medium">{payment.payment_reference}</TableCell>
                                            <TableCell className="font-bold text-green-700 dark:text-green-400">{formatMoney(payment.amount)}</TableCell>
                                            <TableCell className="text-sm capitalize">{payment.payment_method.replace(/_/g, ' ')}</TableCell>
                                            <TableCell className="text-sm">{payment.bank_reference ?? '-'}</TableCell>
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
                                                        className="text-sm text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
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

                        <DataPagination paginator={payments} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}
