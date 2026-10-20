import { Head } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { useState } from 'react';
import { CartesianGrid, Line, LineChart, ReferenceLine, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { FormField } from '@/components/form-field';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { DateRangeFields } from '@/components/reports/date-range-fields';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatMoney, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { fuel } from '@/routes/reports';
import { pdf } from '@/routes/reports/fuel';
import type { BreadcrumbItem } from '@/types';

type ReportRow = {
    id: number;
    name: string | null;
    /** Vehicle type (vehicle view) or the vehicles driven (driver view). */
    detail: string | null;
    fills: number;
    litres: number;
    spend: number;
    distance: number;
    consumption: number | null;
    expected: number | null;
    over_percent: number | null;
    cost_per_km: number | null;
    alerts: number;
    open_alerts: number;
};

type TrendVehicle = { id: number; reg_number: string; expected: number | null; points: (number | null)[] };

type Props = {
    filters: { dateFrom: string; dateTo: string };
    totals: { litres: number; spend: number; distance: number; consumption: number | null; fills: number; open_alerts: number };
    vehicles: ReportRow[];
    drivers: ReportRow[];
    trend: { months: string[]; vehicles: TrendVehicle[] };
    tolerance: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Fuel report', href: fuel() }];

const money = (value: number | null) => (value === null ? '—' : formatMoney(value, 'N$'));
const num = (value: number | null, decimals = 1) => (value === null ? '—' : formatNumber(value, decimals));
const over = (value: number | null) => (value === null ? '—' : `${value > 0 ? '+' : ''}${formatNumber(value, 1)}%`);

/** "2026-09" → "Sep 2026". */
const monthLabel = (month: string) => new Date(`${month}-01T00:00:00`).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });

export default function FuelReport({ filters: initialFilters, totals, vehicles, drivers, trend, tolerance }: Props) {
    const { filters, setFilter } = useFilters(fuel().url, initialFilters);
    const [view, setView] = useState<'vehicles' | 'drivers'>('vehicles');
    const rows = view === 'vehicles' ? vehicles : drivers;

    const pdfUrl = pdf({ query: { dateFrom: filters.dateFrom, dateTo: filters.dateTo } }).url;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fuel report" />
            <PageContainer>
                <PageHeader
                    title="Fuel report"
                    description="Fuel used against the distance in the logbook. Fill-ups count unless rejected."
                    actions={
                        <Button asChild variant="ghost">
                            <a href={pdfUrl}>
                                <FileDown /> Export PDF
                            </a>
                        </Button>
                    }
                />

                <Card>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                            <DateRangeFields dateFrom={filters.dateFrom} dateTo={filters.dateTo} onChange={(key, value) => setFilter(key, value)} />
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:grid-cols-5">
                    <StatCard label="Litres" value={`${num(totals.litres, 0)} L`} hint={`${totals.fills} fill-ups`} />
                    <StatCard label="Spend" value={money(totals.spend)} />
                    <StatCard label="Distance" value={`${num(totals.distance, 0)} km`} hint="From the logbook" />
                    <StatCard emphasis label="Fleet fuel use" value={totals.consumption === null ? '—' : `${num(totals.consumption)} L/100 km`} />
                    <StatCard tone={totals.open_alerts > 0 ? 'negative' : 'neutral'} label="Open alerts" value={totals.open_alerts} />
                </div>

                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1.5">
                            <CardTitle>Fuel use against expected</CardTitle>
                            <CardDescription>
                                Furthest over expected first. Rows more than {tolerance}% over are highlighted. Compare drivers on the same vehicles.
                            </CardDescription>
                        </div>
                        <Tabs value={view} onValueChange={(value) => setView(value as 'vehicles' | 'drivers')}>
                            <TabsList>
                                <TabsTrigger value="vehicles">By vehicle</TabsTrigger>
                                <TabsTrigger value="drivers">By driver</TabsTrigger>
                            </TabsList>
                        </Tabs>
                    </CardHeader>
                    <CardContent>
                        {rows.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">No fill-ups in this period.</p>
                        ) : (
                            <ReportTable rows={rows} view={view} tolerance={tolerance} />
                        )}
                    </CardContent>
                </Card>

                {trend.vehicles.length > 0 && <TrendCard trend={trend} defaultVehicleId={vehicles.find((row) => row.over_percent !== null)?.id ?? trend.vehicles[0].id} />}
            </PageContainer>
        </AppLayout>
    );
}

function ReportTable({ rows, view, tolerance }: { rows: ReportRow[]; view: 'vehicles' | 'drivers'; tolerance: number }) {
    const flagged = (row: ReportRow) => row.over_percent !== null && row.over_percent > tolerance;

    return (
        <>
            {/* Phones: cards */}
            <div className="space-y-3 md:hidden">
                {rows.map((row) => (
                    <div key={row.id} className={cn('space-y-2 rounded-lg border bg-background p-3', flagged(row) && 'border-destructive')}>
                        <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                                <div className={cn('font-medium', view === 'vehicles' && 'font-mono')}>{row.name}</div>
                                {row.detail && <div className="truncate text-sm text-muted-foreground">{row.detail}</div>}
                            </div>
                            <span className={cn('font-mono font-medium tabular-nums', flagged(row) && 'text-destructive')}>{over(row.over_percent)}</span>
                        </div>
                        <dl className="grid grid-cols-2 gap-x-4 gap-y-1 border-t pt-2 text-sm">
                            <dt className="text-muted-foreground">L/100 km</dt>
                            <dd className="text-right font-mono tabular-nums">
                                {num(row.consumption)} <span className="text-muted-foreground">/ {num(row.expected)}</span>
                            </dd>
                            <dt className="text-muted-foreground">Litres · km</dt>
                            <dd className="text-right font-mono tabular-nums">
                                {num(row.litres, 0)} · {num(row.distance, 0)}
                            </dd>
                            <dt className="text-muted-foreground">Spend · per km</dt>
                            <dd className="text-right font-mono tabular-nums">
                                {money(row.spend)} · {money(row.cost_per_km)}
                            </dd>
                            <dt className="text-muted-foreground">Alerts (open)</dt>
                            <dd className="text-right font-mono tabular-nums">
                                {row.alerts} ({row.open_alerts})
                            </dd>
                        </dl>
                    </div>
                ))}
            </div>

            <div className="hidden md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{view === 'vehicles' ? 'Vehicle' : 'Driver'}</TableHead>
                            <TableHead className="text-right">Litres</TableHead>
                            <TableHead className="text-right">Spend</TableHead>
                            <TableHead className="text-right">Distance (km)</TableHead>
                            <TableHead className="text-right">L/100 km</TableHead>
                            <TableHead className="text-right">Expected</TableHead>
                            <TableHead className="text-right">Over expected</TableHead>
                            <TableHead className="text-right">N$ per km</TableHead>
                            <TableHead className="text-right">Alerts (open)</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.id} className={cn(flagged(row) && 'bg-destructive/5')}>
                                <TableCell>
                                    <div className={cn('font-medium', view === 'vehicles' && 'font-mono')}>{row.name}</div>
                                    {row.detail && <div className="max-w-56 truncate text-xs text-muted-foreground">{row.detail}</div>}
                                </TableCell>
                                <TableCell className="text-right font-mono tabular-nums">{num(row.litres, 0)}</TableCell>
                                <TableCell className="text-right font-mono tabular-nums">{money(row.spend)}</TableCell>
                                <TableCell className="text-right font-mono tabular-nums">{num(row.distance, 0)}</TableCell>
                                <TableCell className="text-right font-mono tabular-nums">{num(row.consumption)}</TableCell>
                                <TableCell className="text-right font-mono tabular-nums text-muted-foreground">{num(row.expected)}</TableCell>
                                <TableCell className={cn('text-right font-mono font-medium tabular-nums', flagged(row) && 'text-destructive')}>
                                    {over(row.over_percent)}
                                </TableCell>
                                <TableCell className="text-right font-mono tabular-nums">{money(row.cost_per_km)}</TableCell>
                                <TableCell className="text-right font-mono tabular-nums">
                                    {row.alerts} ({row.open_alerts})
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

/** One vehicle's monthly L/100 km, with its expected rate as a reference line. */
function TrendCard({ trend, defaultVehicleId }: { trend: Props['trend']; defaultVehicleId: number }) {
    const [vehicleId, setVehicleId] = useState(defaultVehicleId);
    const vehicle = trend.vehicles.find((v) => v.id === vehicleId) ?? trend.vehicles[0];
    const data = trend.months.map((month, i) => ({ month: monthLabel(month), consumption: vehicle.points[i] }));
    const hasPoints = vehicle.points.some((point) => point !== null);

    return (
        <Card>
            <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-1.5">
                    <CardTitle>Monthly fuel use: {vehicle.reg_number}</CardTitle>
                    <CardDescription>
                        Litres bought over logbook km each month.{' '}
                        {vehicle.expected !== null ? `Dashed line: expected ${num(vehicle.expected)} L/100 km.` : 'No expected rate yet.'} A gap that has
                        always been there points to the vehicle; a new one points to what changed.
                    </CardDescription>
                </div>
                <FormField label="Vehicle" htmlFor="trend_vehicle" className="sm:w-48">
                    <NativeSelect id="trend_vehicle" value={vehicleId} onChange={(e) => setVehicleId(Number(e.target.value))}>
                        {trend.vehicles.map((v) => (
                            <option key={v.id} value={v.id}>
                                {v.reg_number}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
            </CardHeader>
            <CardContent className="space-y-4">
                {hasPoints ? (
                    <div className="h-64" role="img" aria-label={`Monthly fuel use for ${vehicle.reg_number}, in litres per 100 km`}>
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={data} margin={{ top: 8, right: 16, bottom: 0, left: 0 }}>
                                <CartesianGrid vertical={false} stroke="var(--border)" />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }} />
                                <YAxis
                                    width={44}
                                    tickLine={false}
                                    axisLine={false}
                                    tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                    domain={[0, 'auto']}
                                    unit=" L"
                                />
                                {vehicle.expected !== null && (
                                    <ReferenceLine y={vehicle.expected} stroke="var(--muted-foreground)" strokeDasharray="4 4" strokeWidth={1.5} />
                                )}
                                <Tooltip
                                    cursor={{ stroke: 'var(--border)' }}
                                    contentStyle={{ background: 'var(--popover)', border: '1px solid var(--border)', borderRadius: 6, color: 'var(--popover-foreground)' }}
                                    labelStyle={{ color: 'var(--muted-foreground)' }}
                                    formatter={(value) => [value === null || value === undefined ? 'No data' : `${num(Number(value))} L/100 km`, 'Fuel use']}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="consumption"
                                    stroke="var(--chart-1)"
                                    strokeWidth={2}
                                    dot={{ r: 4, fill: 'var(--chart-1)', stroke: 'var(--card)', strokeWidth: 2 }}
                                    activeDot={{ r: 5 }}
                                    connectNulls={false}
                                    isAnimationActive={false}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                ) : (
                    <p className="py-8 text-center text-muted-foreground">No month has both fill-ups and logbook km for this vehicle.</p>
                )}

                {/* The same figures as a table, for screen readers and exact values. */}
                <details className="text-sm">
                    <summary className="cursor-pointer text-muted-foreground">Show as table</summary>
                    <Table className="mt-2">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Month</TableHead>
                                <TableHead className="text-right">L/100 km</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map((point) => (
                                <TableRow key={point.month}>
                                    <TableCell>{point.month}</TableCell>
                                    <TableCell className="text-right font-mono tabular-nums">{num(point.consumption)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </details>
            </CardContent>
        </Card>
    );
}
