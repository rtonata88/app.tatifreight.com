import { Head, Link } from '@inertiajs/react';
import { Banknote, BarChart3, FileText, MapPin, MoreHorizontal, Search } from 'lucide-react';
import { DataPagination } from '@/components/data-pagination';
import { ClientCell, DistanceCell, LogbookCell, PaymentStatusBadge, VehicleCell, type MdcRow } from '@/components/mdc/mdc-shared';
import { FormField } from '@/components/form-field';
import { Notice } from '@/components/notice';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC charges', href: index() }];

const emptyMessage = 'No MDC calculations found for the selected period.';

export default function MdcIndex({ mdcCalculations, vehicles, can, filters: initialFilters, stats }: Props) {
    const { filters, setFilter, setFilters } = useFilters(index().url, initialFilters);
    // range=custom tells the server the dates were chosen by the user, so clearing one
    // removes the date filter instead of falling back to the current month (as Livewire did).
    const setDate = (key: 'date_from' | 'date_to', value: string) => setFilters((current) => ({ ...current, [key]: value, range: 'custom' }));

    // Above N$50,000 the reminder reads as an error; below that it is a warning.
    const alertTone = stats.total_accumulated > 50000 ? 'error' : 'warning';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC charges" />
            <PageContainer>
                <PageHeader
                    title="MDC charges"
                    actions={
                        <>
                            <div className="hidden gap-2 md:flex">
                                {can.recordPayment && (
                                    <Button asChild>
                                        <Link href={recordPayment()}>
                                            <Banknote /> Record payment
                                        </Link>
                                    </Button>
                                )}
                                <Button asChild variant="ghost">
                                    <Link href={payments()}>
                                        <FileText /> Payment history
                                    </Link>
                                </Button>
                                {can.viewReport && (
                                    <Button asChild variant="ghost">
                                        <Link href={mdcReport()}>
                                            <BarChart3 /> View report
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <div className="flex gap-2 md:hidden">
                                {can.recordPayment && (
                                    <Button asChild>
                                        <Link href={recordPayment()}>
                                            <Banknote /> Record payment
                                        </Link>
                                    </Button>
                                )}
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="icon" aria-label="Actions">
                                            <MoreHorizontal />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="min-w-40">
                                        <DropdownMenuItem asChild>
                                            <Link href={payments()}>
                                                <FileText /> Payment history
                                            </Link>
                                        </DropdownMenuItem>
                                        {can.viewReport && (
                                            <DropdownMenuItem asChild>
                                                <Link href={mdcReport()}>
                                                    <BarChart3 /> View report
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </>
                    }
                />

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-4">
                    <StatCard
                        label="Total accumulated"
                        value={formatMoney(stats.total_accumulated)}
                        hint="Owed to RFANAM"
                        tone="negative"
                        emphasis
                    />
                    <StatCard label="This month" value={formatMoney(stats.this_month)} hint="Current period" />
                    <StatCard
                        label="Total calculations"
                        value={stats.total_count}
                        hint={`Avg: ${formatMoney(stats.average_per_calculation)}`}
                    />
                    <StatCard
                        label="Total distance"
                        value={`${formatNumber(stats.total_distance)} km`}
                        hint={`${formatNumber(stats.total_mass / 1000, 1)}t total mass`}
                    />
                </div>

                {stats.total_accumulated > 10000 && (
                    <Notice tone={alertTone} title="Attention">
                        You have accumulated <span className="font-mono tabular-nums">{formatMoney(stats.total_accumulated)}</span> in MDC charges. Consider
                        making a payment to RFANAM to avoid large outstanding balances.
                    </Notice>
                )}

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <FormField label="Search" htmlFor="search" className="col-span-2 md:col-span-1">
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
                            <FormField label="From date" htmlFor="date_from">
                                <Input id="date_from" type="date" value={filters.date_from} onChange={(e) => setDate('date_from', e.target.value)} />
                            </FormField>
                            <FormField label="To date" htmlFor="date_to">
                                <Input id="date_to" type="date" value={filters.date_to} onChange={(e) => setDate('date_to', e.target.value)} />
                            </FormField>
                            <FormField label="Vehicle" htmlFor="vehicle" className="col-span-2 md:col-span-1">
                                <NativeSelect id="vehicle" value={filters.vehicle} onChange={(e) => setFilter('vehicle', e.target.value)}>
                                    <option value="">All vehicles</option>
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
                                        <TableHead>Logbook entry</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Vehicle</TableHead>
                                        <TableHead>Distance</TableHead>
                                        <TableHead className="text-right">MDC amount</TableHead>
                                        <TableHead className="text-right">Paid</TableHead>
                                        <TableHead className="text-right">Outstanding</TableHead>
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
                                                <TableCell className="text-right font-mono font-bold tabular-nums">{formatMoney(mdc.mdc_amount)}</TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">
                                                    <span className={cn('text-sm', mdc.amount_paid > 0 ? 'font-medium text-success' : 'text-muted-foreground')}>
                                                        {formatMoney(mdc.amount_paid)}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">
                                                    <span
                                                        className={cn(
                                                            'text-sm',
                                                            mdc.outstanding_amount > 0 ? 'font-medium text-warning' : 'text-muted-foreground',
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
                    <div className="text-sm text-muted-foreground">MDC amount</div>
                    <div className="font-mono text-xl font-bold text-destructive tabular-nums">{formatMoney(mdc.mdc_amount)}</div>
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
                    <div className={cn('font-mono font-medium tabular-nums', mdc.amount_paid > 0 ? 'text-success' : 'text-muted-foreground')}>
                        {formatMoney(mdc.amount_paid)}
                    </div>
                </div>
            </div>

            {mdc.outstanding_amount > 0 && (
                <div className="flex items-center justify-between border-t pt-3">
                    <span className="text-sm text-muted-foreground">Outstanding</span>
                    <span className="font-mono text-lg font-bold text-warning tabular-nums">{formatMoney(mdc.outstanding_amount)}</span>
                </div>
            )}
        </div>
    );
}
