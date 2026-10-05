import { Head } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC Report', href: mdcReport() }];

const headings: Record<GroupBy, string> = {
    vehicle: 'MDC by Vehicle',
    client: 'MDC by Client',
    month: 'MDC by Month',
    none: 'All MDC Calculations',
};

const amountClass = 'font-bold text-red-700 dark:text-red-400';

export default function MdcReport(props: Props) {
    const { totalMdc, totalPaid, totalOutstanding, totalCount, totalDistance, totalMass, groupedData } = props;
    const { filters, setFilter } = useFilters(mdcReport().url, props.filters);
    // The table matches the data the server sent, not a group-by still loading.
    const groupBy = props.filters.group_by;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC Report" />
            <PageContainer>
                <PageHeader
                    title="MDC Report"
                    actions={
                        <Button variant="ghost" onClick={() => toast.info('PDF export feature coming soon')}>
                            <FileDown /> Export PDF
                        </Button>
                    }
                />

                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="From Date" htmlFor="date_from">
                                <Input id="date_from" type="date" value={filters.date_from} onChange={(e) => setFilter('date_from', e.target.value)} />
                            </FormField>
                            <FormField label="To Date" htmlFor="date_to">
                                <Input id="date_to" type="date" value={filters.date_to} onChange={(e) => setFilter('date_to', e.target.value)} />
                            </FormField>
                            <FormField label="Group By" htmlFor="group_by">
                                <NativeSelect id="group_by" value={filters.group_by} onChange={(e) => setFilter('group_by', e.target.value as GroupBy)}>
                                    <option value="none">None (All Calculations)</option>
                                    <option value="vehicle">By Vehicle</option>
                                    <option value="client">By Client</option>
                                    <option value="month">By Month</option>
                                </NativeSelect>
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-5">
                    <StatCard label="Total MDC Amount" value={formatMoney(totalMdc)} hint="Total charges" />
                    <StatCard label="Total Paid" value={formatMoney(totalPaid)} hint="Payments made" valueClassName="text-green-700 dark:text-green-400" />
                    <StatCard label="Outstanding" value={formatMoney(totalOutstanding)} hint="Still owed" valueClassName="text-amber-700 dark:text-amber-400" />
                    <StatCard
                        label="Total Calculations"
                        value={totalCount}
                        hint={`${formatNumber(totalDistance)} km total`}
                        valueClassName="text-blue-700 dark:text-blue-400"
                    />
                    <StatCard
                        label="Total Mass"
                        value={`${formatNumber(totalMass / 1000, 1)}t`}
                        hint="Combined mass"
                        valueClassName="text-purple-700 dark:text-purple-400"
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
                        <CardTitle>Period Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="mx-auto max-w-2xl space-y-4">
                            <SummaryLine label="Total Calculations" value={totalCount} />
                            <SummaryLine label="Total Distance Covered" value={`${formatNumber(totalDistance)} km`} />
                            <SummaryLine label="Total Mass Transported" value={`${formatNumber(totalMass / 1000, 1)} tonnes`} />
                            <SummaryLine label="Total MDC Charges" value={formatMoney(totalMdc)} />
                            <SummaryLine
                                label="Total Payments Made"
                                value={formatMoney(totalPaid)}
                                className="bg-green-50 dark:bg-green-900/20"
                                valueClassName="text-green-700 dark:text-green-400"
                            />
                            <div className="flex items-center justify-between gap-4 rounded border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-900/20">
                                <div>
                                    <span className="text-xl font-bold">Outstanding Balance to RFANAM</span>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        For period: {formatDate(props.filters.date_from)} - {formatDate(props.filters.date_to)}
                                    </p>
                                </div>
                                <span className="text-2xl font-bold text-amber-700 dark:text-amber-400">{formatMoney(totalOutstanding)}</span>
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
        <div className={cn('flex items-center justify-between rounded bg-muted p-4', className)}>
            <span className="text-lg font-medium">{label}</span>
            <span className={cn('text-lg font-bold', valueClassName)}>{value}</span>
        </div>
    );
}

function VehicleTable({ rows }: { rows: VehicleGroup[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Vehicle</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Calculations</TableHead>
                    <TableHead>Total Distance</TableHead>
                    <TableHead>Total MDC Amount</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map((row) => (
                    <TableRow key={row.vehicle_id}>
                        <TableCell className="font-bold">{row.reg_number ?? 'N/A'}</TableCell>
                        <TableCell>{row.type ?? 'N/A'}</TableCell>
                        <TableCell>{row.count}</TableCell>
                        <TableCell>{formatNumber(row.total_distance, 2)} km</TableCell>
                        <TableCell className={amountClass}>{formatMoney(row.total_amount)}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function ClientTable({ rows }: { rows: ClientGroup[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Client</TableHead>
                    <TableHead>Calculations</TableHead>
                    <TableHead>Total Distance</TableHead>
                    <TableHead>Total MDC Amount</TableHead>
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
    );
}

function MonthTable({ rows }: { rows: MonthGroup[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Month</TableHead>
                    <TableHead>Calculations</TableHead>
                    <TableHead>Total Distance</TableHead>
                    <TableHead>Total MDC Amount</TableHead>
                    <TableHead>Running Total</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map((row) => (
                    <TableRow key={row.month}>
                        <TableCell className="font-bold">{row.month_label}</TableCell>
                        <TableCell>{row.count}</TableCell>
                        <TableCell>{formatNumber(row.total_distance, 2)} km</TableCell>
                        <TableCell className={amountClass}>{formatMoney(row.total_amount)}</TableCell>
                        <TableCell className="font-bold text-muted-foreground">{formatMoney(row.running_total)}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function CalculationsTable({ rows }: { rows: MdcRow[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Logbook Entry</TableHead>
                    <TableHead>Client</TableHead>
                    <TableHead>Vehicle</TableHead>
                    <TableHead>Distance</TableHead>
                    <TableHead>GVM</TableHead>
                    <TableHead>MDC Amount</TableHead>
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
    );
}
