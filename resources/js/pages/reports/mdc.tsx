import { Head } from '@inertiajs/react';
import { FileDown, MapPin } from 'lucide-react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { ClientCell, LogbookCell, VehicleCell, type MdcRow } from '@/components/mdc/mdc-shared';
import { FormField } from '@/components/form-field';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { mdc as mdcReport } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';

type GroupBy = 'none' | 'vehicle' | 'client' | 'month';

type VehicleGroup = { vehicle_id: number; reg_number: string | null; type: string | null; count: number; total_distance: number; total_amount: number };
type ClientGroup = { client_id: number; client: { name: string; company_name: string | null } | null; count: number; total_distance: number; total_amount: number };
type MonthGroup = { month: string; month_label: string; count: number; total_distance: number; total_amount: number; running_total: number };

type Props = {
    filters: { date_from: string; date_to: string; group_by: GroupBy };
    totalMdc: number;
    totalPaid: number;
    totalOutstanding: number;
    totalCount: number;
    totalDistance: number;
    totalMass: number;
    avgPerCalculation: number;
    groupedData: unknown[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC report', href: mdcReport() }];

const headings: Record<GroupBy, string> = {
    vehicle: 'MDC by vehicle',
    client: 'MDC by client',
    month: 'MDC by month',
    none: 'All MDC calculations',
};

const amountClass = 'text-right font-mono font-bold tabular-nums';

export default function MdcReport(props: Props) {
    const { totalMdc, totalPaid, totalOutstanding, totalCount, totalDistance, totalMass, groupedData } = props;
    const { filters, setFilter } = useFilters(mdcReport().url, props.filters);
    // The table matches the data the server sent, not a group-by still loading.
    const groupBy = props.filters.group_by;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC report" />
            <PageContainer>
                <PageHeader
                    title="MDC report"
                    actions={
                        <Button variant="ghost" onClick={() => toast.info('PDF export feature coming soon')}>
                            <FileDown /> Export PDF
                        </Button>
                    }
                />

                <Card>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                            <FormField label="From date" htmlFor="date_from">
                                <Input id="date_from" type="date" value={filters.date_from} onChange={(e) => setFilter('date_from', e.target.value)} />
                            </FormField>
                            <FormField label="To date" htmlFor="date_to">
                                <Input id="date_to" type="date" value={filters.date_to} onChange={(e) => setFilter('date_to', e.target.value)} />
                            </FormField>
                            <FormField label="Group by" htmlFor="group_by" className="col-span-2 md:col-span-1">
                                <NativeSelect id="group_by" value={filters.group_by} onChange={(e) => setFilter('group_by', e.target.value as GroupBy)}>
                                    <option value="none">None (all calculations)</option>
                                    <option value="vehicle">By vehicle</option>
                                    <option value="client">By client</option>
                                    <option value="month">By month</option>
                                </NativeSelect>
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-2 xl:grid-cols-5">
                    <StatCard label="Total MDC amount" value={formatMoney(totalMdc)} hint="Total charges" />
                    <StatCard label="Total paid" value={formatMoney(totalPaid)} hint="Payments made" tone="positive" />
                    <StatCard label="Outstanding" value={formatMoney(totalOutstanding)} hint="Still owed" tone="negative" emphasis />
                    <StatCard
                        label="Total calculations"
                        value={totalCount}
                        hint={`${formatNumber(totalDistance)} km total`}
                    />
                    <StatCard
                        label="Total mass"
                        value={`${formatNumber(totalMass / 1000, 1)}t`}
                        hint="Combined mass"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{headings[groupBy]}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {groupBy === 'vehicle' && <VehicleTable rows={groupedData as VehicleGroup[]} />}
                        {groupBy === 'client' && <ClientTable rows={groupedData as ClientGroup[]} />}
                        {groupBy === 'month' && <MonthTable rows={groupedData as MonthGroup[]} />}
                        {groupBy === 'none' && <CalculationsTable rows={groupedData as MdcRow[]} />}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Period summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="mx-auto max-w-2xl space-y-4">
                            <SummaryLine label="Total calculations" value={totalCount} />
                            <SummaryLine label="Total distance covered" value={`${formatNumber(totalDistance)} km`} />
                            <SummaryLine label="Total mass transported" value={`${formatNumber(totalMass / 1000, 1)} tonnes`} />
                            <SummaryLine label="Total MDC charges" value={formatMoney(totalMdc)} />
                            <SummaryLine
                                label="Total payments made"
                                value={formatMoney(totalPaid)}
                                className="bg-(--nx-pos-wash)"
                                valueClassName="text-success"
                            />
                            <div className="flex flex-col items-start gap-2 rounded border border-warning bg-(--nx-warn-wash) p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 md:p-6">
                                <div>
                                    <span className="text-lg font-bold md:text-xl">Outstanding balance to RFANAM</span>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        For period: {formatDate(props.filters.date_from)} - {formatDate(props.filters.date_to)}
                                    </p>
                                </div>
                                <span className="font-condensed text-2xl font-bold whitespace-nowrap text-warning tabular-nums">
                                    {formatMoney(totalOutstanding)}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function SummaryLine({ label, value, className, valueClassName }: { label: string; value: ReactNode; className?: string; valueClassName?: string }) {
    return (
        <div className={cn('flex items-center justify-between gap-3 rounded bg-muted p-3 md:p-4', className)}>
            <span className="text-sm font-medium md:text-lg">{label}</span>
            <span className={cn('font-mono text-sm font-bold whitespace-nowrap tabular-nums md:text-lg', valueClassName)}>{value}</span>
        </div>
    );
}

/** Phones: one card per group — label on the left, amount on the right, facts underneath. */
function MobileRow({ title, amount, children }: { title: ReactNode; amount: ReactNode; children: ReactNode }) {
    return (
        <div className="space-y-2 rounded-lg border p-3 text-sm">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">{title}</div>
                <span className="font-mono font-bold whitespace-nowrap tabular-nums">{amount}</span>
            </div>
            <div className="grid grid-cols-2 gap-x-4 gap-y-1 border-t pt-2">{children}</div>
        </div>
    );
}

function MobileFigure({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="tabular-nums">{value}</div>
        </div>
    );
}

function VehicleTable({ rows }: { rows: VehicleGroup[] }) {
    return (
        <>
            <div className="space-y-3 md:hidden">
                {rows.map((row) => (
                    <MobileRow
                        key={row.vehicle_id}
                        title={
                            <>
                                <div className="font-mono font-bold">{row.reg_number ?? 'N/A'}</div>
                                <div className="text-xs text-muted-foreground">{row.type ?? 'N/A'}</div>
                            </>
                        }
                        amount={formatMoney(row.total_amount)}
                    >
                        <MobileFigure label="Calculations" value={row.count} />
                        <MobileFigure label="Total distance" value={`${formatNumber(row.total_distance, 2)} km`} />
                    </MobileRow>
                ))}
            </div>
            <div className="hidden md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Vehicle</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Calculations</TableHead>
                            <TableHead>Total distance</TableHead>
                            <TableHead className="text-right">Total MDC amount</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.vehicle_id}>
                                <TableCell className="font-mono font-bold">{row.reg_number ?? 'N/A'}</TableCell>
                                <TableCell>{row.type ?? 'N/A'}</TableCell>
                                <TableCell>{row.count}</TableCell>
                                <TableCell>{formatNumber(row.total_distance, 2)} km</TableCell>
                                <TableCell className={amountClass}>{formatMoney(row.total_amount)}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

function ClientTable({ rows }: { rows: ClientGroup[] }) {
    return (
        <>
            <div className="space-y-3 md:hidden">
                {rows.map((row) => (
                    <MobileRow
                        key={row.client_id}
                        title={
                            row.client ? (
                                <>
                                    <div className="font-bold">{row.client.name}</div>
                                    {row.client.company_name && <div className="text-xs text-muted-foreground">{row.client.company_name}</div>}
                                </>
                            ) : (
                                'N/A'
                            )
                        }
                        amount={formatMoney(row.total_amount)}
                    >
                        <MobileFigure label="Calculations" value={row.count} />
                        <MobileFigure label="Total distance" value={`${formatNumber(row.total_distance, 2)} km`} />
                    </MobileRow>
                ))}
            </div>
            <div className="hidden md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Client</TableHead>
                            <TableHead>Calculations</TableHead>
                            <TableHead>Total distance</TableHead>
                            <TableHead className="text-right">Total MDC amount</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.client_id}>
                                <TableCell>
                                    {row.client ? (
                                        <div>
                                            <strong>{row.client.name}</strong>
                                            {row.client.company_name && <div className="text-sm text-muted-foreground">{row.client.company_name}</div>}
                                        </div>
                                    ) : (
                                        'N/A'
                                    )}
                                </TableCell>
                                <TableCell>{row.count}</TableCell>
                                <TableCell>{formatNumber(row.total_distance, 2)} km</TableCell>
                                <TableCell className={amountClass}>{formatMoney(row.total_amount)}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

function MonthTable({ rows }: { rows: MonthGroup[] }) {
    return (
        <>
            <div className="space-y-3 md:hidden">
                {rows.map((row) => (
                    <MobileRow key={row.month} title={<span className="font-bold">{row.month_label}</span>} amount={formatMoney(row.total_amount)}>
                        <MobileFigure label="Calculations" value={row.count} />
                        <MobileFigure label="Total distance" value={`${formatNumber(row.total_distance, 2)} km`} />
                        <MobileFigure
                            label="Running total"
                            value={<span className="font-mono text-muted-foreground">{formatMoney(row.running_total)}</span>}
                        />
                    </MobileRow>
                ))}
            </div>
            <div className="hidden md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Month</TableHead>
                            <TableHead>Calculations</TableHead>
                            <TableHead>Total distance</TableHead>
                            <TableHead className="text-right">Total MDC amount</TableHead>
                            <TableHead className="text-right">Running total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.month}>
                                <TableCell className="font-bold">{row.month_label}</TableCell>
                                <TableCell>{row.count}</TableCell>
                                <TableCell>{formatNumber(row.total_distance, 2)} km</TableCell>
                                <TableCell className={amountClass}>{formatMoney(row.total_amount)}</TableCell>
                                <TableCell className="text-right font-mono font-bold text-muted-foreground tabular-nums">
                                    {formatMoney(row.running_total)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

function CalculationsTable({ rows }: { rows: MdcRow[] }) {
    return (
        <>
            <div className="space-y-3 md:hidden">
                {rows.map((mdc) => (
                    <CalculationCard key={mdc.id} mdc={mdc} />
                ))}
            </div>
            <div className="hidden md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Logbook entry</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Vehicle</TableHead>
                            <TableHead>Distance</TableHead>
                            <TableHead>GVM</TableHead>
                            <TableHead className="text-right">MDC amount</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((mdc) => (
                            <TableRow key={mdc.id}>
                                <TableCell>{formatDate(mdc.calculation_date)}</TableCell>
                                <TableCell>
                                    <LogbookCell logbook={mdc.logbook} showBooking />
                                </TableCell>
                                <TableCell>
                                    <ClientCell client={mdc.client} />
                                </TableCell>
                                <TableCell>
                                    {mdc.vehicle ? <VehicleCell vehicle={mdc.vehicle} /> : <span className="font-medium">N/A</span>}
                                </TableCell>
                                <TableCell>{formatNumber(mdc.distance_km, 2)} km</TableCell>
                                <TableCell>{mdc.gvm_tonnes ? `${formatNumber(mdc.gvm_tonnes, 2)}t` : 'N/A'}</TableCell>
                                <TableCell className={amountClass}>{formatMoney(mdc.mdc_amount)}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

/** Phones: one calculation, laid out like the card on the MDC charges page. */
function CalculationCard({ mdc }: { mdc: MdcRow }) {
    return (
        <div className="space-y-3 rounded-lg border p-4">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <div className="font-bold">{formatDate(mdc.calculation_date)}</div>
                    {mdc.logbook?.booking_number && (
                        <div className="text-xs text-muted-foreground">
                            Booking: <span className="font-mono">{mdc.logbook.booking_number}</span>
                        </div>
                    )}
                </div>
                <div className="text-right">
                    <div className="text-xs text-muted-foreground">MDC amount</div>
                    <div className="font-mono text-lg font-bold whitespace-nowrap tabular-nums">{formatMoney(mdc.mdc_amount)}</div>
                </div>
            </div>

            {mdc.logbook && (
                <div className="space-y-1 border-t pt-3 text-sm">
                    <div className="text-xs text-muted-foreground">Logbook {formatDate(mdc.logbook.date)}</div>
                    <div className="flex items-center gap-2">
                        <MapPin className="size-4 text-muted-foreground" />
                        <span className="font-medium">{mdc.logbook.origin_from}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <MapPin className="size-4 text-muted-foreground" />
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
                    <div className="font-medium tabular-nums">{formatNumber(mdc.distance_km, 2)} km</div>
                </div>
                <div>
                    <div className="text-xs text-muted-foreground">GVM</div>
                    <div className="font-medium tabular-nums">{mdc.gvm_tonnes ? `${formatNumber(mdc.gvm_tonnes, 2)}t` : 'N/A'}</div>
                </div>
            </div>
        </div>
    );
}
