import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Download, FileText, Pencil, Trash2, XCircle } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { ReviewAlertDialog } from '@/components/fuel/review-alert-dialog';
import { expenseStatusTone, ucfirst } from '@/components/expenses/expense-meta';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { formatDate, formatDateTime, formatMoney, formatNumber } from '@/lib/format';
import { approve, destroy, edit, index, reject, show } from '@/routes/expenses';
import { download } from '@/routes/expenses/receipt';
import { index as bookingsIndex } from '@/routes/bookings';
import { show as vehicleShow } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';

type ExpenseDetail = {
    id: number;
    amount: number;
    status: string;
    category: string;
    category_label: string;
    description: string | null;
    expense_date: string | null;
    notes: string | null;
    vehicle: { id: number; reg_number: string; type: string | null } | null;
    booking: { id: number; booking_number: string; client: string | null } | null;
    submitted_by: string | null;
    created_at: string | null;
    approved_by: string | null;
    approved_at: string | null;
    has_receipt: boolean;
    receipt_url: string | null;
    receipt_extension: string | null;
    receipt_is_image: boolean;
    fuel: {
        driver: string | null;
        litres: number | null;
        price_per_litre: number | null;
        odometer: number | null;
        full_tank: boolean | null;
        station: string | null;
        consumption: number | null;
        expected_consumption: number | null;
        odometer_photo_url: string | null;
    } | null;
};

type FuelAlertRow = {
    id: number;
    label: string;
    message: string;
    reviewed: boolean;
    reviewed_by: string | null;
    reviewed_at: string | null;
    review_note: string | null;
};

type Props = {
    expense: ExpenseDetail;
    fuelAlerts: FuelAlertRow[];
    can: { edit: boolean; delete: boolean; reviewFuelAlerts: boolean };
};

const money = (value: number) => formatMoney(value, 'N$');

/** "04 Nov 2025, 14:30" — the old format('d M Y, H:i'). */
const dateTimeComma = (value: string | null) => formatDateTime(value).replace(/ (\d{2}:\d{2})$/, ', $1');

export default function ExpensesShow({ expense, fuelAlerts, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Expenses', href: index() },
        { title: `Expense #${expense.id}`, href: show(expense.id) },
    ];

    const statusBadge = <StatusBadge tone={expenseStatusTone[expense.status] ?? 'gray'}>{ucfirst(expense.status)}</StatusBadge>;
    const categoryBadge = <StatusBadge tone="gray">{expense.category_label}</StatusBadge>;

    const approveExpense = () => router.post(approve(expense.id).url, {}, { preserveScroll: true });
    const rejectExpense = () => router.post(reject(expense.id).url, {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Expense details" />
            <PageContainer>
                <PageHeader
                    title="Expense details"
                    actions={
                        <>
                            <Button asChild variant="ghost">
                                <Link href={index()}>
                                    <ArrowLeft /> Back to expenses
                                </Link>
                            </Button>
                            {can.edit && (
                                <Button asChild>
                                    <Link href={edit(expense.id)}>
                                        <Pencil /> Edit expense
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {/* Header card */}
                <Card>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex items-center gap-4">
                                <h2 className="font-condensed text-3xl font-bold tabular-nums">{money(expense.amount)}</h2>
                                {statusBadge}
                            </div>
                            <p className="mt-2 text-lg text-muted-foreground">{expense.description}</p>
                            <p className="mt-1">{categoryBadge}</p>
                        </div>
                        {/* Phones: approval sits here, not ~900px down in Quick actions. */}
                        {can.edit && expense.status === 'pending' && (
                            <div className="grid grid-cols-2 gap-2 md:hidden">
                                <Button onClick={approveExpense}>
                                    <CheckCircle2 /> Approve
                                </Button>
                                <Button variant="destructive" onClick={rejectExpense}>
                                    <XCircle /> Reject
                                </Button>
                            </div>
                        )}
                        {/* Phones get the receipt card's download button further down. */}
                        {expense.has_receipt && (
                            <Button asChild variant="ghost" size="sm" className="hidden md:inline-flex">
                                <a href={download(expense.id).url}>
                                    <Download /> Download receipt
                                </a>
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {fuelAlerts.length > 0 && (
                    <Card className={fuelAlerts.some((a) => !a.reviewed) ? 'border-destructive' : undefined}>
                        <CardHeader>
                            <CardTitle className={fuelAlerts.some((a) => !a.reviewed) ? 'text-destructive' : undefined}>Fuel alerts</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {fuelAlerts.map((alert) => (
                                <div key={alert.id} className="flex flex-col gap-2 rounded-lg border bg-background p-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-1">
                                        <StatusBadge tone={alert.reviewed ? 'gray' : 'red'}>{alert.label}</StatusBadge>
                                        <p className="text-sm">{alert.message}</p>
                                        {alert.reviewed && (
                                            <p className="text-xs text-muted-foreground">
                                                Reviewed by {alert.reviewed_by} on {dateTimeComma(alert.reviewed_at)}: {alert.review_note}
                                            </p>
                                        )}
                                    </div>
                                    {!alert.reviewed && can.reviewFuelAlerts && <ReviewAlertDialog alertId={alert.id} summary={`${alert.label}: ${alert.message}`} />}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Left column */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Expense information</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 gap-4">
                                <Detail label="Expense date">{formatDate(expense.expense_date)}</Detail>
                                <Detail label="Category">{categoryBadge}</Detail>
                                <Detail label="Amount" valueClassName="font-mono text-lg tabular-nums">
                                    {money(expense.amount)}
                                </Detail>
                                <Detail label="Status">{statusBadge}</Detail>
                                <Detail label="Description" className="col-span-2">
                                    {expense.description}
                                </Detail>
                            </CardContent>
                        </Card>

                        {expense.fuel && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Fill-up</CardTitle>
                                </CardHeader>
                                <CardContent className="grid grid-cols-2 gap-4">
                                    <Detail label="Litres" valueClassName="font-mono tabular-nums">
                                        {expense.fuel.litres !== null ? `${formatNumber(expense.fuel.litres, 2)} L` : '—'}
                                    </Detail>
                                    <Detail label="Price per litre" valueClassName="font-mono tabular-nums">
                                        {expense.fuel.price_per_litre !== null ? formatMoney(expense.fuel.price_per_litre, 'N$') : '—'}
                                    </Detail>
                                    <Detail label="Odometer" valueClassName="font-mono tabular-nums">
                                        {expense.fuel.odometer !== null ? `${formatNumber(expense.fuel.odometer, 0)} km` : '—'}
                                    </Detail>
                                    <Detail label="Tank filled to full">
                                        {expense.fuel.full_tank === null ? '—' : expense.fuel.full_tank ? 'Yes, until the pump clicked off' : 'No, only topped up'}
                                    </Detail>
                                    <Detail label="Station">{expense.fuel.station ?? '—'}</Detail>
                                    <Detail label="Driver">{expense.fuel.driver ?? '—'}</Detail>
                                    <Detail label="Fuel use" valueClassName="font-mono tabular-nums">
                                        {expense.fuel.consumption !== null ? `${formatNumber(expense.fuel.consumption, 1)} L/100 km` : 'Measured at the next full tank'}
                                    </Detail>
                                    <Detail label="Expected" valueClassName="font-mono tabular-nums">
                                        {expense.fuel.expected_consumption !== null ? `${formatNumber(expense.fuel.expected_consumption, 1)} L/100 km` : 'Learned from the first five full tanks'}
                                    </Detail>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Related information</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 gap-4">
                                {expense.vehicle && (
                                    <div>
                                        <span className="text-sm text-muted-foreground">Vehicle</span>
                                        <p className="font-medium">
                                            <Link href={vehicleShow(expense.vehicle.id)} className="font-mono text-primary hover:underline">
                                                {expense.vehicle.reg_number}
                                            </Link>
                                        </p>
                                        {expense.vehicle.type && <p className="text-xs text-muted-foreground">{expense.vehicle.type}</p>}
                                    </div>
                                )}

                                {expense.booking && (
                                    <div>
                                        <span className="text-sm text-muted-foreground">Booking</span>
                                        <p className="font-medium">
                                            {/* bookings.index belongs to another module; literal URL avoids depending on its generated routes. */}
                                            <Link href={bookingsIndex()} className="font-mono text-primary hover:underline">
                                                {expense.booking.booking_number}
                                            </Link>
                                        </p>
                                        {expense.booking.client && <p className="text-xs text-muted-foreground">{expense.booking.client}</p>}
                                    </div>
                                )}

                                <div>
                                    <span className="text-sm text-muted-foreground">Submitted by</span>
                                    <p className="font-medium">{expense.submitted_by}</p>
                                    <p className="text-xs text-muted-foreground">{dateTimeComma(expense.created_at)}</p>
                                </div>

                                {expense.approved_by && (
                                    <div>
                                        <span className="text-sm text-muted-foreground">Approved by</span>
                                        <p className="font-medium">{expense.approved_by}</p>
                                        {expense.approved_at && <p className="text-xs text-muted-foreground">{dateTimeComma(expense.approved_at)}</p>}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {expense.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap text-foreground/80">{expense.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right column */}
                    <div className="space-y-6">
                        {expense.has_receipt && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Receipt</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {expense.receipt_is_image && expense.receipt_url ? (
                                        <div className="overflow-hidden rounded-lg border">
                                            <img src={expense.receipt_url} alt="Receipt" className="h-auto max-h-96 w-full object-contain" />
                                        </div>
                                    ) : (
                                        <div className="rounded-lg bg-muted p-8 text-center">
                                            <FileText className="mx-auto mb-4 size-16 text-muted-foreground" />
                                            <p className="text-sm text-muted-foreground">PDF receipt</p>
                                            <p className="mt-1 text-xs text-muted-foreground">{expense.receipt_extension?.toUpperCase()} file</p>
                                        </div>
                                    )}
                                    <Button asChild className="w-full">
                                        <a href={download(expense.id).url}>
                                            <Download /> Download receipt
                                        </a>
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {expense.fuel?.odometer_photo_url && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Odometer photo</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-hidden rounded-lg border">
                                        <img src={expense.fuel.odometer_photo_url} alt="Dashboard odometer" className="h-auto max-h-96 w-full object-contain" />
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {can.edit && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Quick actions</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {expense.status === 'pending' && (
                                        <div className="hidden space-y-2 md:block">
                                            <Button className="w-full" onClick={approveExpense}>
                                                <CheckCircle2 /> Approve expense
                                            </Button>
                                            <Button variant="destructive" className="w-full" onClick={rejectExpense}>
                                                <XCircle /> Reject expense
                                            </Button>
                                        </div>
                                    )}
                                    <Button asChild variant="ghost" className="w-full">
                                        <Link href={edit(expense.id)}>
                                            <Pencil /> Edit expense
                                        </Link>
                                    </Button>
                                    {can.delete && (
                                        <ConfirmDialog
                                            trigger={
                                                <Button variant="destructive" className="w-full">
                                                    <Trash2 /> Delete expense
                                                </Button>
                                            }
                                            description="Are you sure you want to delete this expense?"
                                            onConfirm={(done) => router.delete(destroy(expense.id).url, { data: { redirect: 'index' }, onFinish: done })}
                                        />
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Status history</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <HistoryItem dotClassName="bg-info" title="Created" by={expense.submitted_by} at={dateTimeComma(expense.created_at)} />
                                {expense.approved_by && (
                                    <HistoryItem
                                        dotClassName={expense.status === 'approved' ? 'bg-success' : 'bg-destructive'}
                                        title={ucfirst(expense.status)}
                                        by={expense.approved_by}
                                        at={expense.approved_at ? dateTimeComma(expense.approved_at) : null}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </PageContainer>
        </AppLayout>
    );
}

function Detail({ label, children, className, valueClassName }: { label: string; children: React.ReactNode; className?: string; valueClassName?: string }) {
    return (
        <div className={className}>
            <span className="text-sm text-muted-foreground">{label}</span>
            <div className={cn('font-medium', valueClassName)}>{children}</div>
        </div>
    );
}

function HistoryItem({ dotClassName, title, by, at }: { dotClassName: string; title: string; by: string | null; at: string | null }) {
    return (
        <div className="flex items-start gap-3">
            <div className={cn('mt-2 size-2 shrink-0 rounded-full', dotClassName)} />
            <div className="flex-1">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-xs text-muted-foreground">{by}</p>
                {at && <p className="text-xs text-muted-foreground/70">{at}</p>}
            </div>
        </div>
    );
}
