import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { toneText, type ReportTone } from '@/components/reports/report-tiles';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ReviewAlertDialog } from '@/components/fuel/review-alert-dialog';
import { show as expenseShow } from '@/routes/expenses';
import { recordPayment } from '@/routes/mdc';
import { dashboard } from '@/routes/reports';
import { company } from '@/routes/settings';
import type { BreadcrumbItem } from '@/types';

type TopClient = {
    client_id: number;
    name: string | null;
    company_name: string | null;
    total_revenue: number;
};
type CategoryTotal = { category: string; total: number };
type MaintenanceAlert = {
    key: string;
    vehicle_id: number;
    reg_number: string;
    type: string | null;
    kind: 'insurance' | 'disc';
    label: string;
    expiry_date: string;
    expired: boolean;
    days: number;
};

type MdcReminder = {
    key: string;
    vehicle_id: number;
    reg_number: string | null;
    month: string;
    charges: number;
    outstanding: number;
    due_date: string;
    overdue: boolean;
    days: number;
};

type FuelAlertRow = {
    id: number;
    expense_id: number;
    type: string;
    label: string;
    message: string;
    reg_number: string | null;
    driver: string | null;
    date: string;
};

type Props = {
    filters: { dateFrom: string; dateTo: string };
    totalRevenue: number;
    pendingRevenue: number;
    overdueRevenue: number;
    totalExpenses: number;
    pendingExpenses: number;
    profit: number;
    profitMargin: number;
    completedBookings: number;
    activeBookings: number;
    utilizationRate: number;
    totalVehicles: number;
    vehiclesInUse: number;
    topClients: TopClient[];
    monthlyRevenue: { month: string; revenue: number }[];
    expensesByCategory: CategoryTotal[];
    maintenanceAlerts: MaintenanceAlert[];
    /** Null when the user cannot view MDC. */
    mdcReminders: { due_day: number | null; has_outstanding: boolean; rows: MdcReminder[] } | null;
    canRecordMdcPayment: boolean;
    /** Null when the user does not review expenses. */
    fuelAlerts: { total: number; rows: FuelAlertRow[] } | null;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Analytics', href: dashboard() }];

/** The old view used "N$1,234.50" (no space) via number_format. */
const money = (value: number) => formatMoney(value, 'N$');

/** Phones: figures two-up (the third spans both); from md they stack as before. */
const figureGrid = 'grid grid-cols-2 gap-2 max-md:[&>*:last-child]:col-span-2 md:grid-cols-1 md:gap-4';

export default function ReportsDashboard(props: Props) {
    const { filters, setFilter } = useFilters(dashboard().url, props.filters);
    const {
        totalRevenue,
        pendingRevenue,
        overdueRevenue,
        totalExpenses,
        profit,
        profitMargin,
        completedBookings,
        activeBookings,
        utilizationRate,
        totalVehicles,
        vehiclesInUse,
        topClients,
        expensesByCategory,
        maintenanceAlerts,
        mdcReminders,
        canRecordMdcPayment,
        fuelAlerts,
    } = props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics dashboard" />
            <PageContainer>
                <PageHeader
                    title="Analytics dashboard"
                    actions={
                        <div className="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                            <Input
                                type="date"
                                aria-label="From date"
                                value={filters.dateFrom}
                                onChange={(e) => setFilter('dateFrom', e.target.value)}
                            />
                            <Input type="date" aria-label="To date" value={filters.dateTo} onChange={(e) => setFilter('dateTo', e.target.value)} />
                        </div>
                    }
                />

                {/* Key metrics */}
                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:grid-cols-4">
                    <StatCard label="Total revenue" value={money(totalRevenue)} hint="Paid invoices" />
                    <StatCard label="Total expenses" value={money(totalExpenses)} hint="Approved expenses" />
                    <StatCard
                        tone={profit >= 0 ? 'positive' : 'negative'}
                        emphasis
                        label="Net profit"
                        value={money(profit)}
                        hint={`${formatNumber(profitMargin, 1)}% margin`}
                    />
                    <StatCard
                        label="Fleet utilization"
                        value={`${formatNumber(utilizationRate, 1)}%`}
                        hint={`${vehiclesInUse}/${totalVehicles} in use`}
                    />
                </div>

                {/* Maintenance alerts (only shown when there are any), right under the key metrics */}
                {maintenanceAlerts.length > 0 && (
                    <Card className="border-destructive">
                        <CardHeader>
                            <CardTitle className="text-destructive">Maintenance alerts</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 md:hidden">
                                {maintenanceAlerts.map((alert) => (
                                    <div key={alert.key} className="space-y-2 rounded-lg border bg-background p-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <div className="font-mono font-medium">{alert.reg_number}</div>
                                                <div className="text-sm text-muted-foreground">{alert.type}</div>
                                            </div>
                                            <AlertBadge alert={alert} />
                                        </div>
                                        <div className="flex justify-between border-t pt-2 text-sm">
                                            <span className="font-medium">{formatDate(alert.expiry_date)}</span>
                                            <DaysRemaining alert={alert} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="hidden md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Vehicle</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Alert</TableHead>
                                            <TableHead>Expiry date</TableHead>
                                            <TableHead>Days remaining</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {maintenanceAlerts.map((alert) => (
                                            <TableRow key={alert.key}>
                                                <TableCell className="font-mono font-medium">{alert.reg_number}</TableCell>
                                                <TableCell>{alert.type}</TableCell>
                                                <TableCell>
                                                    <AlertBadge alert={alert} />
                                                </TableCell>
                                                <TableCell className="font-medium">{formatDate(alert.expiry_date)}</TableCell>
                                                <TableCell>
                                                    <DaysRemaining alert={alert} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {fuelAlerts && fuelAlerts.rows.length > 0 && <FuelAlerts alerts={fuelAlerts} />}

                {mdcReminders && <MdcReminders reminders={mdcReminders} canRecordPayment={canRecordMdcPayment} />}

                {/* Revenue & bookings */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Revenue status</CardTitle>
                        </CardHeader>
                        <CardContent className={figureGrid}>
                            <StackedFigure tone="green" label="Collected" value={money(totalRevenue)} />
                            <StackedFigure tone="yellow" label="Pending" value={money(pendingRevenue)} />
                            <StackedFigure tone="red" label="Overdue" value={money(overdueRevenue)} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Booking activity</CardTitle>
                        </CardHeader>
                        <CardContent className={figureGrid}>
                            <StackedFigure tone="gray" label="Completed bookings" value={completedBookings} />
                            <StackedFigure tone="gray" label="Active bookings" value={activeBookings} />
                            <StackedFigure
                                tone="gray"
                                label="Avg revenue per booking"
                                value={completedBookings > 0 ? money(totalRevenue / completedBookings) : 'N$0.00'}
                            />
                        </CardContent>
                    </Card>
                </div>

                {/* Top clients */}
                <Card>
                    <CardHeader>
                        <CardTitle>Top clients by revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {topClients.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">No revenue data for selected period</p>
                        ) : (
                            <>
                                <div className="space-y-3 md:hidden">
                                    {topClients.map((item, index) => (
                                        <div key={item.client_id} className="flex items-center justify-between gap-3 rounded-lg border p-3">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <StatusBadge tone={index === 0 ? 'yellow' : 'gray'}>#{index + 1}</StatusBadge>
                                                <div className="min-w-0">
                                                    <div className="truncate font-medium">{item.name}</div>
                                                    <div className="truncate text-sm text-muted-foreground">{item.company_name || '-'}</div>
                                                </div>
                                            </div>
                                            <div className="shrink-0 font-mono font-bold tabular-nums">
                                                {money(item.total_revenue)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Rank</TableHead>
                                                <TableHead>Client</TableHead>
                                                <TableHead>Company</TableHead>
                                                <TableHead className="text-right">Revenue</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {topClients.map((item, index) => (
                                                <TableRow key={item.client_id}>
                                                    <TableCell>
                                                        <StatusBadge tone={index === 0 ? 'yellow' : 'gray'}>#{index + 1}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell className="font-medium">{item.name}</TableCell>
                                                    <TableCell className="text-muted-foreground">{item.company_name || '-'}</TableCell>
                                                    <TableCell className="text-right font-mono font-bold tabular-nums">
                                                        {money(item.total_revenue)}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Expenses breakdown */}
                <Card>
                    <CardHeader>
                        <CardTitle>Expenses by category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {expensesByCategory.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">No expense data for selected period</p>
                        ) : (
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                                {expensesByCategory.map((expense) => (
                                    <div key={expense.category} className="rounded-md border bg-muted p-4">
                                        <p className="text-sm text-muted-foreground capitalize">{expense.category.replace(/_/g, ' ')}</p>
                                        <p className="font-mono text-lg font-bold tabular-nums">{money(expense.total)}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {totalExpenses > 0 ? formatNumber((expense.total / totalExpenses) * 100, 1) : 0}% of total
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

            </PageContainer>
        </AppLayout>
    );
}

function StackedFigure({ tone, label, value }: { tone: ReportTone; label: string; value: ReactNode }) {
    return (
        <div className="min-w-0 rounded-md border bg-muted p-3 md:p-4">
            <p className="text-xs text-muted-foreground md:text-sm">{label}</p>
            <p className={cn('font-mono text-base font-bold tabular-nums md:text-lg', toneText[tone])}>{value}</p>
        </div>
    );
}

function AlertBadge({ alert }: { alert: MaintenanceAlert }) {
    return (
        <StatusBadge tone={alert.expired ? 'red' : 'yellow'}>
            {alert.label} {alert.expired ? 'Expired' : 'Expiring'}
        </StatusBadge>
    );
}

function DaysRemaining({ alert }: { alert: MaintenanceAlert }) {
    return (
        <span className={cn('font-medium', alert.expired ? 'text-destructive' : 'text-warning')}>
            {alert.expired ? `Expired ${alert.days} days ago` : `${alert.days} days`}
        </span>
    );
}

/** "2026-09" → "Sep 2026". */
const monthLabel = (month: string) =>
    new Date(`${month}-01T00:00:00`).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });

/** Outstanding MDC that is overdue or falls due soon; shown only when there is something to act on. */
function MdcReminders({ reminders, canRecordPayment }: { reminders: NonNullable<Props['mdcReminders']>; canRecordPayment: boolean }) {
    const { due_day, has_outstanding, rows } = reminders;

    if (!due_day) {
        if (!has_outstanding) return null;

        return (
            <Card className="border-warning">
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-1.5">
                        <CardTitle>MDC payment reminders</CardTitle>
                        <CardDescription>There is unpaid MDC, but no payment due day is set, so reminders are off.</CardDescription>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={company()}>Set due day</Link>
                    </Button>
                </CardHeader>
            </Card>
        );
    }

    if (rows.length === 0) return null;

    const overdue = rows.some((row) => row.overdue);
    const total = rows.reduce((sum, row) => sum + row.outstanding, 0);

    return (
        <Card className={overdue ? 'border-destructive' : 'border-warning'}>
            <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-1.5">
                    <CardTitle className={overdue ? 'text-destructive' : undefined}>MDC payment reminders</CardTitle>
                    <CardDescription>
                        {money(total)} outstanding. Each month's MDC is due on day {due_day} of the following month.
                    </CardDescription>
                </div>
                {canRecordPayment && (
                    <Button asChild>
                        <Link href={recordPayment()}>Record payment</Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                <div className="space-y-3 md:hidden">
                    {rows.map((row) => (
                        <div key={row.key} className="space-y-2 rounded-lg border bg-background p-3">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <div className="font-mono font-medium">{row.reg_number}</div>
                                    <div className="text-sm text-muted-foreground">
                                        {monthLabel(row.month)} · {row.charges} {row.charges === 1 ? 'charge' : 'charges'}
                                    </div>
                                </div>
                                <span className="font-mono font-medium tabular-nums">{money(row.outstanding)}</span>
                            </div>
                            <div className="flex justify-between border-t pt-2 text-sm">
                                <span className="font-medium">Due {formatDate(row.due_date)}</span>
                                <MdcDue row={row} />
                            </div>
                        </div>
                    ))}
                </div>
                <div className="hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Month</TableHead>
                                <TableHead className="text-right">Charges</TableHead>
                                <TableHead className="text-right">Outstanding</TableHead>
                                <TableHead>Due date</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow key={row.key}>
                                    <TableCell className="font-mono font-medium">{row.reg_number}</TableCell>
                                    <TableCell>{monthLabel(row.month)}</TableCell>
                                    <TableCell className="text-right tabular-nums">{row.charges}</TableCell>
                                    <TableCell className="text-right font-mono tabular-nums">{money(row.outstanding)}</TableCell>
                                    <TableCell className="font-medium">{formatDate(row.due_date)}</TableCell>
                                    <TableCell>
                                        <MdcDue row={row} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

function MdcDue({ row }: { row: MdcReminder }) {
    if (row.overdue) {
        return <span className="font-medium text-destructive">Overdue by {row.days} {row.days === 1 ? 'day' : 'days'}</span>;
    }

    return <span className="font-medium text-warning">{row.days === 0 ? 'Due today' : `Due in ${row.days} ${row.days === 1 ? 'day' : 'days'}`}</span>;
}

/** Fill-ups that failed a fuel check and have not been reviewed yet, newest first. */
function FuelAlerts({ alerts }: { alerts: NonNullable<Props['fuelAlerts']> }) {
    const more = alerts.total - alerts.rows.length;

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="text-destructive">Fuel alerts</CardTitle>
                <CardDescription>
                    {alerts.total} {alerts.total === 1 ? 'fill-up check needs' : 'fill-up checks need'} a look. An alert is a reason to ask, not proof.
                    {more > 0 && ` Showing the newest ${alerts.rows.length}.`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-3 md:hidden">
                    {alerts.rows.map((alert) => (
                        <div key={alert.id} className="space-y-2 rounded-lg border bg-background p-3">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <Link href={expenseShow(alert.expense_id)} className="font-mono font-medium text-primary hover:underline">
                                        {alert.reg_number ?? 'No vehicle'}
                                    </Link>
                                    <div className="text-sm text-muted-foreground">
                                        {alert.driver ?? 'Unknown driver'} · {formatDate(alert.date)}
                                    </div>
                                </div>
                                <StatusBadge tone="red">{alert.label}</StatusBadge>
                            </div>
                            <p className="text-sm">{alert.message}</p>
                            <ReviewAlertDialog alertId={alert.id} summary={`${alert.label}: ${alert.message}`} />
                        </div>
                    ))}
                </div>
                <div className="hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Driver</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Alert</TableHead>
                                <TableHead>Details</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {alerts.rows.map((alert) => (
                                <TableRow key={alert.id}>
                                    <TableCell className="font-mono font-medium">
                                        <Link href={expenseShow(alert.expense_id)} className="text-primary hover:underline">
                                            {alert.reg_number ?? 'No vehicle'}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{alert.driver ?? '—'}</TableCell>
                                    <TableCell>{formatDate(alert.date)}</TableCell>
                                    <TableCell>
                                        <StatusBadge tone="red">{alert.label}</StatusBadge>
                                    </TableCell>
                                    <TableCell className="max-w-md whitespace-normal">{alert.message}</TableCell>
                                    <TableCell className="text-right">
                                        <ReviewAlertDialog alertId={alert.id} summary={`${alert.label}: ${alert.message}`} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
