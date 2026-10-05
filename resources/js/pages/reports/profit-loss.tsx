import { Head } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { DateRangeFields } from '@/components/reports/date-range-fields';
import { AmountRow, MetricCard, toneSurface, toneText } from '@/components/reports/report-tiles';
import { FormField } from '@/components/form-field';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/native-select';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatMoney, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { profitLoss } from '@/routes/reports';
import { pdf } from '@/routes/reports/profit-loss';
import type { BreadcrumbItem } from '@/types';

type Props = {
    filters: { dateFrom: string; dateTo: string; groupBy: string };
    revenue: {
        vehicle_rentals: number;
        distance_based: number;
        cargo_services: number;
    };
    totalRevenue: number;
    expenses: {
        fuel: number;
        maintenance: number;
        mdc_payment: number;
        insurance: number;
        licenses: number;
        wages: number;
        tolls: number;
        other: number;
    };
    totalExpenses: number;
    grossProfit: number;
    netProfit: number;
    profitMargin: number;
    trendingData: { period: string; revenue: number }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Profit & Loss', href: profitLoss() }];

const money = (value: number) => formatMoney(value, 'N$');

const expenseLines: { key: keyof Props['expenses']; label: string }[] = [
    { key: 'fuel', label: 'Fuel' },
    { key: 'maintenance', label: 'Maintenance & Repairs' },
    { key: 'mdc_payment', label: 'MDC Payments to RFANAM' },
    { key: 'insurance', label: 'Insurance' },
    { key: 'licenses', label: 'Licenses & Permits' },
    { key: 'wages', label: 'Driver Wages' },
    { key: 'tolls', label: 'Tolls' },
    { key: 'other', label: 'Other Expenses' },
];

export default function ProfitLossReport({
    filters: initialFilters,
    revenue,
    totalRevenue,
    expenses,
    totalExpenses,
    grossProfit,
    netProfit,
    profitMargin,
}: Props) {
    const { filters, setFilter } = useFilters(profitLoss().url, initialFilters);
    const positive = netProfit >= 0;

    // The PDF controller reads dateFrom / dateTo (same params the old exportPdf() redirect sent).
    const pdfUrl = pdf({
        query: { dateFrom: filters.dateFrom, dateTo: filters.dateTo },
    }).url;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profit & Loss Statement" />
            <PageContainer>
                <PageHeader
                    title="Profit & Loss Statement"
                    actions={
                        <Button asChild variant="ghost">
                            <a href={pdfUrl}>
                                <FileDown /> Export PDF
                            </a>
                        </Button>
                    }
                />

                {/* Date range filters */}
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <DateRangeFields dateFrom={filters.dateFrom} dateTo={filters.dateTo} onChange={(key, value) => setFilter(key, value)} />
                            <FormField label="Group By" htmlFor="groupBy">
                                <NativeSelect id="groupBy" value={filters.groupBy} onChange={(e) => setFilter('groupBy', e.target.value)}>
                                    <option value="month">Monthly</option>
                                    <option value="quarter">Quarterly</option>
                                    <option value="year">Yearly</option>
                                </NativeSelect>
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <MetricCard tone="green" size="lg" label="Total Revenue" value={money(totalRevenue)} />
                    <MetricCard tone="red" size="lg" label="Total Expenses" value={money(totalExpenses)} />
                    <MetricCard
                        tone={positive ? 'blue' : 'orange'}
                        size="lg"
                        label="Net Profit"
                        value={money(netProfit)}
                        hint={<span className="text-sm">Margin: {formatNumber(profitMargin, 1)}%</span>}
                    />
                </div>

                {/* Detailed statement */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg text-green-700 dark:text-green-400">Revenue</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <AmountRow
                                tone="green"
                                label="Time-Based Rentals"
                                sublabel="Day, Hour, Week, Month"
                                value={money(revenue.vehicle_rentals)}
                            />
                            <AmountRow tone="green" label="Distance-Based Services" sublabel="Trip, Km" value={money(revenue.distance_based)} />
                            <AmountRow
                                tone="green"
                                label="Cargo Services"
                                sublabel="Tonne, Load, Pallet, Container, etc."
                                value={money(revenue.cargo_services)}
                            />
                            <AmountRow tone="green" emphasis label="Total Revenue" value={money(totalRevenue)} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg text-red-700 dark:text-red-400">Operating Expenses</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {expenseLines.map((line) => (
                                <AmountRow
                                    key={line.key}
                                    tone="red"
                                    label={<span className="font-normal">{line.label}</span>}
                                    value={money(expenses[line.key])}
                                />
                            ))}
                            <AmountRow tone="red" emphasis label="Total Expenses" value={money(totalExpenses)} />
                        </CardContent>
                    </Card>
                </div>

                {/* Final summary */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Profit Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="mx-auto max-w-2xl space-y-4">
                            <div className="flex items-center justify-between gap-4 rounded-md bg-muted/60 p-4">
                                <span className="text-lg font-medium">Gross Profit</span>
                                <span className="text-lg font-bold text-green-600 tabular-nums dark:text-green-400">{money(grossProfit)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-4 rounded-md bg-muted/60 p-4">
                                <span className="text-lg font-medium">Less: Operating Expenses</span>
                                <span className="text-lg font-bold text-red-600 tabular-nums dark:text-red-400">{money(totalExpenses)}</span>
                            </div>
                            <div
                                className={cn(
                                    'flex flex-col gap-2 rounded-md border-2 p-6 sm:flex-row sm:items-center sm:justify-between',
                                    toneSurface[positive ? 'blue' : 'orange'],
                                )}
                            >
                                <div>
                                    <span className="text-xl font-bold">Net Profit</span>
                                    <p className="mt-1 text-sm text-muted-foreground">Profit Margin: {formatNumber(profitMargin, 1)}%</p>
                                </div>
                                <span className={cn('text-2xl font-bold tabular-nums', toneText[positive ? 'blue' : 'orange'])}>
                                    {money(netProfit)}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}
