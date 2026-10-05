import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Banknote, BarChart3, Calendar, ClipboardList, DollarSign, FileText, MapPin, MoreHorizontal, Search } from 'lucide-react';
import { DataPagination } from '@/components/data-pagination';
import { ClientCell, DistanceCell, LogbookCell, PaymentStatusBadge, VehicleCell, type MdcRow } from '@/components/mdc/mdc-shared';
import { FormField } from '@/components/form-field';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index, payments, recordPayment } from '@/routes/mdc';
import { mdc as mdcReport } from '@/routes/reports';
import type { BreadcrumbItem, Option, Paginated } from '@/types';

type Props = {
    mdcCalculations: Paginated<MdcRow>;
    vehicles: Option[];
    can: { recordPayment: boolean; viewReport: boolean };
    filters: { search: string; date_from: string; date_to: string; vehicle: string; range: string };
    stats: {
        total_accumulated: number;
        total_paid: number;
        total_outstanding: number;
        total_count: number;
        this_month: number;
        average_per_calculation: number;
        total_distance: number;
        total_mass: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC Charges', href: index() }];

const emptyMessage = 'No MDC calculations found for the selected period.';

export default function MdcIndex({ mdcCalculations, vehicles, can, filters: initialFilters, stats }: Props) {
    const { filters, setFilter, setFilters } = useFilters(index().url, initialFilters);
    // range=custom tells the server the dates were chosen by the user, so clearing one
    // removes the date filter instead of falling back to the current month (as Livewire did).
    const setDate = (key: 'date_from' | 'date_to', value: string) => setFilters((current) => ({ ...current, [key]: value, range: 'custom' }));

    const level = stats.total_accumulated > 50000 ? 'red' : stats.total_accumulated > 25000 ? 'orange' : 'yellow';
    const alertClasses = {
        red: 'border-red-500 bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-300',
        orange: 'border-orange-500 bg-orange-50 text-orange-800 dark:bg-orange-500/10 dark:text-orange-300',
        yellow: 'border-yellow-500 bg-yellow-50 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-300',
    }[level];
    const alertIconClasses = { red: 'text-red-400', orange: 'text-orange-400', yellow: 'text-yellow-400' }[level];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC Charges" />
            <PageContainer>
                <PageHeader
                    title="MDC Charges"
                    actions={
                        <>
                            <div className="hidden gap-2 md:flex">
                                {can.recordPayment && (
                                    <Button asChild>
                                        <Link href={recordPayment()}>
                                            <Banknote /> Record Payment
                                        </Link>
                                    </Button>
                                )}
                                <Button asChild variant="ghost">
                                    <Link href={payments()}>
                                        <FileText /> Payment History
                                    </Link>
                                </Button>
                                {can.viewReport && (
                                    <Button asChild variant="ghost">
                                        <Link href={mdcReport()}>
                                            <BarChart3 /> View Report
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <div className="md:hidden">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="icon" aria-label="Actions">
                                            <MoreHorizontal />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="min-w-40">
                                        {can.recordPayment && (
                                            <DropdownMenuItem asChild>
                                                <Link href={recordPayment()}>
                                                    <Banknote /> Record Payment
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem asChild>
                                            <Link href={payments()}>
                                                <FileText /> Payment History
                                            </Link>
                                        </DropdownMenuItem>
                                        {can.viewReport && (
                                            <DropdownMenuItem asChild>
                                                <Link href={mdcReport()}>
                                                    <BarChart3 /> View Report
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </>
                    }
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <StatCard
                        label="Total Accumulated"
                        value={formatMoney(stats.total_accumulated)}
                        hint="Owed to RFANAM"
                        icon={DollarSign}
                        valueClassName="text-red-700 dark:text-red-400"
                    />
                    <StatCard
                        label="This Month"
                        value={formatMoney(stats.this_month)}
                        hint="Current period"
                        icon={Calendar}
                        valueClassName="text-blue-700 dark:text-blue-400"
                    />
                    <StatCard
                        label="Total Calculations"
                        value={stats.total_count}
                        hint={`Avg: ${formatMoney(stats.average_per_calculation)}`}
                        icon={ClipboardList}
                        valueClassName="text-purple-700 dark:text-purple-400"
                    />
                    <StatCard
                        label="Total Distance"
                        value={`${formatNumber(stats.total_distance)} km`}
                        hint={`${formatNumber(stats.total_mass / 1000, 1)}t total mass`}
                        icon={MapPin}
                        valueClassName="text-green-700 dark:text-green-400"
                    />
                </div>

                {stats.total_accumulated > 10000 && (
                    <div className={cn('flex gap-3 rounded-lg border-l-4 p-4', alertClasses)}>
                        <AlertTriangle className={cn('size-5 shrink-0', alertIconClasses)} />
                        <p className="text-sm font-medium">
                            <strong>Attention:</strong> You have accumulated {formatMoney(stats.total_accumulated)} in MDC charges. Consider making a payment to
                            RFANAM to avoid large outstanding balances.
                        </p>
                    </div>
                )}

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Search" htmlFor="search">
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        className="pl-9"
                                        value={filters.search}
                                        onChange={(e) => setFilter('search', e.target.value)}
                                        placeholder="Client, vehicle, or trip purpose..."
                                    />
                                </div>
                            </FormField>
                            <FormField label="From Date" htmlFor="date_from">
                                <Input id="date_from" type="date" value={filters.date_from} onChange={(e) => setDate('date_from', e.target.value)} />
                            </FormField>
                            <FormField label="To Date" htmlFor="date_to">
                                <Input id="date_to" type="date" value={filters.date_to} onChange={(e) => setDate('date_to', e.target.value)} />
                            </FormField>
                            <FormField label="Vehicle" htmlFor="vehicle">
                                <NativeSelect id="vehicle" value={filters.vehicle} onChange={(e) => setFilter('vehicle', e.target.value)}>
                                    <option value="">All Vehicles</option>
                                    {vehicles.map((vehicle) => (
                                        <option key={vehicle.value} value={vehicle.value}>
                                            {vehicle.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                        </div>

                        {/* Phones: cards */}
                        <div className="space-y-4 md:hidden">
                            {mdcCalculations.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">{emptyMessage}</div>
                            ) : (
                                mdcCalculations.data.map((mdc) => <MdcCard key={mdc.id} mdc={mdc} />)
                            )}
                        </div>

                        {/* Tablets and up: table */}
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Logbook Entry</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Vehicle</TableHead>
                                        <TableHead>Distance</TableHead>
                                        <TableHead>MDC Amount</TableHead>
                                        <TableHead>Paid</TableHead>
                                        <TableHead>Outstanding</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {mdcCalculations.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={9} className="py-8 text-center text-muted-foreground">
                                                {emptyMessage}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        mdcCalculations.data.map((mdc) => (
                                            <TableRow key={mdc.id}>
                                                <TableCell className="text-sm">{formatDate(mdc.calculation_date)}</TableCell>
                                                <TableCell>
                                                    <LogbookCell logbook={mdc.logbook} />
                                                </TableCell>
                                                <TableCell>
                                                    <ClientCell client={mdc.client} />
                                                </TableCell>
                                                <TableCell>
                                                    <VehicleCell vehicle={mdc.vehicle} />
                                                </TableCell>
                                                <TableCell>
                                                    <DistanceCell row={mdc} />
                                                </TableCell>
                                                <TableCell className="font-bold">{formatMoney(mdc.mdc_amount)}</TableCell>
                                                <TableCell>
                                                    <span className={cn('text-sm', mdc.amount_paid > 0 ? 'font-medium text-green-700 dark:text-green-400' : 'text-muted-foreground')}>
                                                        {formatMoney(mdc.amount_paid)}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span
                                                        className={cn(
                                                            'text-sm',
                                                            mdc.outstanding_amount > 0 ? 'font-medium text-amber-700 dark:text-amber-400' : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {formatMoney(mdc.outstanding_amount)}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <PaymentStatusBadge status={mdc.payment_status} />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <DataPagination paginator={mdcCalculations} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function MdcCard({ mdc }: { mdc: MdcRow }) {
    return (
        <div className="space-y-3 rounded-lg border p-4">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <div className="text-lg font-bold">{formatDate(mdc.calculation_date)}</div>
                    {mdc.logbook && <div className="text-sm text-muted-foreground">{formatDate(mdc.logbook.date)}</div>}
                </div>
                <div className="text-right">
                    <div className="text-sm text-muted-foreground">MDC Amount</div>
                    <div className="text-xl font-bold text-red-700 dark:text-red-400">{formatMoney(mdc.mdc_amount)}</div>
                </div>
            </div>

            <PaymentStatusBadge status={mdc.payment_status} />

            {mdc.logbook && (
                <div className="space-y-1 border-t pt-3 text-sm">
                    <div className="flex items-center gap-2">
                        <MapPin className="size-4 text-muted-foreground" />
                        <span className="font-medium">{mdc.logbook.origin_from}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <MapPin className="ml-1 size-4 text-muted-foreground" />
                        <span className="font-medium">{mdc.logbook.origin_to}</span>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-2 gap-3 border-t pt-3 text-sm">
                <div>
                    <div className="text-xs text-muted-foreground">Client</div>
                    <ClientCell client={mdc.client} />
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">Vehicle</div>
                    <VehicleCell vehicle={mdc.vehicle} />
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">Distance</div>
                    <div className="font-medium">
                        <DistanceCell row={mdc} />
                    </div>
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">Paid</div>
                    <div className={cn('font-medium', mdc.amount_paid > 0 ? 'text-green-700 dark:text-green-400' : 'text-muted-foreground')}>
                        {formatMoney(mdc.amount_paid)}
                    </div>
                </div>
            </div>

            {mdc.outstanding_amount > 0 && (
                <div className="flex items-center justify-between border-t pt-3">
                    <span className="text-sm text-muted-foreground">Outstanding</span>
                    <span className="text-lg font-bold text-amber-700 dark:text-amber-400">{formatMoney(mdc.outstanding_amount)}</span>
                </div>
            )}
        </div>
    );
}
