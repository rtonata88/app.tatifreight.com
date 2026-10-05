import { Head } from '@inertiajs/react';
import { Download, Info } from 'lucide-react';
import { Fragment } from 'react';
import { DateRangeFields } from '@/components/reports/date-range-fields';
import { MetricCard, toneSurface } from '@/components/reports/report-tiles';
import { FormField } from '@/components/form-field';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import { vat } from '@/routes/reports';
import { pdf } from '@/routes/reports/vat';
import type { BreadcrumbItem } from '@/types';

type MonthRow = {
    period: string;
    sales: number;
    vat_output: number;
    purchases: number;
    vat_input: number;
    vat_payable: number;
};

type Props = {
    filters: { dateFrom: string; dateTo: string; groupBy: string };
    vatOutput: {
        total_sales: number;
        vat_collected: number;
        total_with_vat: number;
    };
    vatInput: {
        total_purchases: number;
        vat_paid: number;
        total_with_vat: number;
    };
    vatPayable: number;
    monthlyBreakdown: MonthRow[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'VAT Report', href: vat() }];

const money = (value: number) => formatMoney(value, 'N$');

const payableText = (value: number) => (value >= 0 ? 'text-red-700 dark:text-red-400' : 'text-purple-700 dark:text-purple-400');

export default function VatReport({ filters: initialFilters, vatOutput, vatInput, vatPayable, monthlyBreakdown }: Props) {
    const { filters, setFilter } = useFilters(vat().url, initialFilters);
    const payable = vatPayable >= 0;

    // The PDF controller reads dateFrom / dateTo (same params the old exportPdf() redirect sent).
    const pdfUrl = pdf({
        query: { dateFrom: filters.dateFrom, dateTo: filters.dateTo },
    }).url;

    // Rendered from the server props so the table only shows once the server has
    // recomputed it for "By Month", as the old view did.
    const showBreakdown = initialFilters.groupBy === 'month' && monthlyBreakdown.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="VAT Report" />
            <PageContainer>
                <PageHeader
                    title="VAT Report"
                    actions={
                        <Button asChild variant="ghost">
                            <a href={pdfUrl}>
                                <Download /> Export to PDF
                            </a>
                        </Button>
                    }
                />

                {/* Filters */}
                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 md:max-w-3xl">
                            <DateRangeFields
                                dateFrom={filters.dateFrom}
                                dateTo={filters.dateTo}
                                fromLabel="Date From"
                                toLabel="Date To"
                                onChange={(key, value) => setFilter(key, value)}
                            />
                            <FormField label="Group By" htmlFor="groupBy">
                                <NativeSelect id="groupBy" value={filters.groupBy} onChange={(e) => setFilter('groupBy', e.target.value)}>
                                    <option value="none">No Grouping</option>
                                    <option value="month">By Month</option>
                                    <option value="quarter">By Quarter</option>
                                </NativeSelect>
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary cards */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <MetricCard
                        tone="green"
                        size="lg"
                        label="VAT Output (Collected)"
                        value={money(vatOutput.vat_collected)}
                        hint={`Sales: ${money(vatOutput.total_sales)}`}
                    />
                    <MetricCard
                        tone="blue"
                        size="lg"
                        label="VAT Input (Paid)"
                        value={money(vatInput.vat_paid)}
                        hint={`Purchases: ${money(vatInput.total_purchases)}`}
                    />
                    <MetricCard
                        tone={payable ? 'red' : 'purple'}
                        size="lg"
                        label={payable ? 'VAT Payable' : 'VAT Refundable'}
                        value={money(Math.abs(vatPayable))}
                        hint={`Due to ${payable ? 'Inland Revenue' : 'be Refunded'}`}
                    />
                </div>

                {/* Detailed VAT calculation */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">VAT Calculation Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableBody>
                                <SectionRow className={toneSurface.green}>OUTPUT VAT (VAT on Sales)</SectionRow>
                                <LineRow label="Sales (Excluding VAT)" value={money(vatOutput.total_sales)} />
                                <LineRow label="VAT @ 15%" value={money(vatOutput.vat_collected)} />
                                <LineRow label="Total Sales (Including VAT)" value={money(vatOutput.total_with_vat)} total />

                                <SpacerRow />

                                <SectionRow className={toneSurface.blue}>INPUT VAT (VAT on Purchases)</SectionRow>
                                <LineRow label="Purchases (Excluding VAT)" value={money(vatInput.total_purchases)} />
                                <LineRow label="VAT @ 15%" value={money(vatInput.vat_paid)} />
                                <LineRow label="Total Purchases (Including VAT)" value={money(vatInput.total_with_vat)} total />

                                <SpacerRow />

                                <TableRow className={cn('hover:bg-transparent', toneSurface[payable ? 'red' : 'purple'])}>
                                    <TableCell className="py-3 font-bold whitespace-normal">
                                        {payable ? 'VAT PAYABLE TO INLAND REVENUE' : 'VAT REFUNDABLE FROM INLAND REVENUE'}
                                    </TableCell>
                                    <TableCell className="py-3 text-right text-lg font-bold tabular-nums">{money(Math.abs(vatPayable))}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Monthly breakdown */}
                {showBreakdown && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Monthly VAT Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {/* Phones: one card per month */}
                            <div className="space-y-3 md:hidden">
                                {monthlyBreakdown.map((month) => (
                                    <div key={month.period} className="space-y-2 rounded-lg border p-3 text-sm">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="font-medium">{month.period}</span>
                                            <PayableAmount value={month.vat_payable} />
                                        </div>
                                        <div className="grid grid-cols-2 gap-x-4 gap-y-1 border-t pt-2">
                                            <MobileFigure label="Sales" value={money(month.sales)} />
                                            <MobileFigure
                                                label="VAT Output"
                                                value={money(month.vat_output)}
                                                className="text-green-700 dark:text-green-400"
                                            />
                                            <MobileFigure label="Purchases" value={money(month.purchases)} />
                                            <MobileFigure
                                                label="VAT Input"
                                                value={money(month.vat_input)}
                                                className="text-blue-700 dark:text-blue-400"
                                            />
                                        </div>
                                    </div>
                                ))}
                                <div className="flex items-center justify-between rounded-lg border bg-muted p-3 text-sm font-bold">
                                    <span>TOTAL</span>
                                    <PayableAmount value={vatPayable} />
                                </div>
                            </div>

                            {/* Tablets and up: table */}
                            <div className="hidden md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="text-xs uppercase">Period</TableHead>
                                            <TableHead className="text-right text-xs uppercase">Sales</TableHead>
                                            <TableHead className="text-right text-xs uppercase">VAT Output</TableHead>
                                            <TableHead className="text-right text-xs uppercase">Purchases</TableHead>
                                            <TableHead className="text-right text-xs uppercase">VAT Input</TableHead>
                                            <TableHead className="text-right text-xs uppercase">VAT Payable</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {monthlyBreakdown.map((month) => (
                                            <TableRow key={month.period}>
                                                <TableCell className="font-medium">{month.period}</TableCell>
                                                <TableCell className="text-right tabular-nums">{money(month.sales)}</TableCell>
                                                <TableCell className="text-right text-green-700 tabular-nums dark:text-green-400">
                                                    {money(month.vat_output)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">{money(month.purchases)}</TableCell>
                                                <TableCell className="text-right text-blue-700 tabular-nums dark:text-blue-400">
                                                    {money(month.vat_input)}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    <PayableAmount value={month.vat_payable} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell className="font-bold">TOTAL</TableCell>
                                            <TableCell className="text-right font-bold tabular-nums">{money(vatOutput.total_sales)}</TableCell>
                                            <TableCell className="text-right font-bold text-green-700 tabular-nums dark:text-green-400">
                                                {money(vatOutput.vat_collected)}
                                            </TableCell>
                                            <TableCell className="text-right font-bold tabular-nums">{money(vatInput.total_purchases)}</TableCell>
                                            <TableCell className="text-right font-bold text-blue-700 tabular-nums dark:text-blue-400">
                                                {money(vatInput.vat_paid)}
                                            </TableCell>
                                            <TableCell className="text-right font-bold">
                                                <PayableAmount value={vatPayable} />
                                            </TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Information note */}
                <Card className={cn('shadow-none', toneSurface.blue)}>
                    <CardContent className="flex gap-3">
                        <Info className="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                        <div className="text-sm text-blue-900 dark:text-blue-200">
                            <p className="mb-1 font-semibold">VAT Calculation Notes:</p>
                            <ul className="list-inside list-disc space-y-1 text-blue-800 dark:text-blue-300">
                                <li>VAT Output is calculated from paid and partially paid invoices</li>
                                <li>VAT Input is calculated from approved expenses (assuming VAT is included in expense amounts)</li>
                                <li>Standard VAT rate of 15% is applied</li>
                                <li>This report should be reviewed by your accountant before submission to Inland Revenue</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function SectionRow({ className, children }: { className?: string; children: string }) {
    return (
        <TableRow className={cn('hover:bg-transparent', className)}>
            <TableCell colSpan={2} className="py-3 font-semibold whitespace-normal">
                {children}
            </TableCell>
        </TableRow>
    );
}

function LineRow({ label, value, total = false }: { label: string; value: string; total?: boolean }) {
    return (
        <TableRow className={cn(total && 'bg-muted/50')}>
            <TableCell className={cn('whitespace-normal', total ? 'font-semibold' : 'text-muted-foreground')}>{label}</TableCell>
            <TableCell className={cn('text-right tabular-nums', total ? 'font-bold' : 'font-medium')}>{value}</TableCell>
        </TableRow>
    );
}

function SpacerRow() {
    return (
        <TableRow className="border-0 hover:bg-transparent">
            <TableCell colSpan={2} className="h-4 p-0" />
        </TableRow>
    );
}

function PayableAmount({ value }: { value: number }) {
    return (
        <span className={cn('tabular-nums', payableText(value))}>
            {money(Math.abs(value))}
            {value < 0 && <span className="ml-1 text-xs">(Refund)</span>}
        </span>
    );
}

function MobileFigure({ label, value, className }: { label: string; value: string; className?: string }) {
    return (
        <Fragment>
            <span className="text-muted-foreground">{label}</span>
            <span className={cn('text-right tabular-nums', className)}>{value}</span>
        </Fragment>
    );
}
