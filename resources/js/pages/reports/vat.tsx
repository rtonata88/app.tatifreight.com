import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Fragment } from 'react';
import { DateRangeFields } from '@/components/reports/date-range-fields';
import { FormField } from '@/components/form-field';
import { Notice } from '@/components/notice';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'VAT report', href: vat() }];

const money = (value: number) => formatMoney(value, 'N$');

const payableText = (value: number) => (value >= 0 ? 'text-destructive' : 'text-success');

export default function VatReport({ filters: initialFilters, vatOutput, vatInput, vatPayable, monthlyBreakdown }: Props) {
    const { filters, setFilter } = useFilters(vat().url, initialFilters);
    const payable = vatPayable >= 0;

    // The PDF controller reads dateFrom / dateTo (same params the old exportPdf() redirect sent).
    const pdfUrl = pdf({
        query: { dateFrom: filters.dateFrom, dateTo: filters.dateTo },
    }).url;

    // Rendered from the server props so the table only shows once the server has
    // recomputed it for "By month", as the old view did.
    const showBreakdown = initialFilters.groupBy === 'month' && monthlyBreakdown.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="VAT report" />
            <PageContainer>
                <PageHeader
                    title="VAT report"
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
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:max-w-3xl">
                            <DateRangeFields
                                dateFrom={filters.dateFrom}
                                dateTo={filters.dateTo}
                                fromLabel="Date from"
                                toLabel="Date to"
                                onChange={(key, value) => setFilter(key, value)}
                            />
                            <FormField label="Group by" htmlFor="groupBy" className="col-span-2 sm:col-span-1">
                                <NativeSelect id="groupBy" value={filters.groupBy} onChange={(e) => setFilter('groupBy', e.target.value)}>
                                    <option value="none">No grouping</option>
                                    <option value="month">By month</option>
                                    <option value="quarter">By quarter</option>
                                </NativeSelect>
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary cards */}
                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-3">
                    <StatCard label="VAT output (collected)" value={money(vatOutput.vat_collected)} hint={`Sales: ${money(vatOutput.total_sales)}`} />
                    <StatCard label="VAT input (paid)" value={money(vatInput.vat_paid)} hint={`Purchases: ${money(vatInput.total_purchases)}`} />
                    <StatCard
                        tone={payable ? 'negative' : 'positive'}
                        emphasis
                        label={payable ? 'VAT payable' : 'VAT refundable'}
                        value={money(Math.abs(vatPayable))}
                        hint={`Due to ${payable ? 'Inland Revenue' : 'be refunded'}`}
                    />
                </div>

                {/* Detailed VAT calculation */}
                <Card>
                    <CardHeader>
                        <CardTitle>VAT calculation summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableBody>
                                <SectionRow>Output VAT (VAT on sales)</SectionRow>
                                <LineRow label="Sales (excluding VAT)" value={money(vatOutput.total_sales)} />
                                <LineRow label="VAT @ 15%" value={money(vatOutput.vat_collected)} />
                                <LineRow label="Total sales (including VAT)" value={money(vatOutput.total_with_vat)} total />

                                <SpacerRow />

                                <SectionRow>Input VAT (VAT on purchases)</SectionRow>
                                <LineRow label="Purchases (excluding VAT)" value={money(vatInput.total_purchases)} />
                                <LineRow label="VAT @ 15%" value={money(vatInput.vat_paid)} />
                                <LineRow label="Total purchases (including VAT)" value={money(vatInput.total_with_vat)} total />

                                <SpacerRow />

                                <TableRow className={cn('hover:bg-transparent', payable ? 'bg-(--nx-neg-wash)' : 'bg-(--nx-pos-wash)')}>
                                    <TableCell className="py-3 font-bold whitespace-normal">
                                        {payable ? 'VAT payable to Inland Revenue' : 'VAT refundable from Inland Revenue'}
                                    </TableCell>
                                    <TableCell
                                        className={cn(
                                            'py-3 text-right font-mono text-lg font-bold tabular-nums',
                                            payable ? 'text-destructive' : 'text-success',
                                        )}
                                    >
                                        {money(Math.abs(vatPayable))}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Monthly breakdown */}
                {showBreakdown && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Monthly VAT breakdown</CardTitle>
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
                                            <MobileFigure label="VAT output" value={money(month.vat_output)} />
                                            <MobileFigure label="Purchases" value={money(month.purchases)} />
                                            <MobileFigure label="VAT input" value={money(month.vat_input)} />
                                        </div>
                                    </div>
                                ))}
                                <div className="flex items-center justify-between rounded-lg border bg-muted p-3 text-sm font-bold">
                                    <span>Total</span>
                                    <PayableAmount value={vatPayable} />
                                </div>
                            </div>

                            {/* Tablets and up: table */}
                            <div className="hidden md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Period</TableHead>
                                            <TableHead className="text-right">Sales</TableHead>
                                            <TableHead className="text-right">VAT output</TableHead>
                                            <TableHead className="text-right">Purchases</TableHead>
                                            <TableHead className="text-right">VAT input</TableHead>
                                            <TableHead className="text-right">VAT payable</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {monthlyBreakdown.map((month) => (
                                            <TableRow key={month.period}>
                                                <TableCell className="font-medium">{month.period}</TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">{money(month.sales)}</TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">{money(month.vat_output)}</TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">{money(month.purchases)}</TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">{money(month.vat_input)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    <PayableAmount value={month.vat_payable} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell className="font-bold">Total</TableCell>
                                            <TableCell className="text-right font-mono font-bold tabular-nums">
                                                {money(vatOutput.total_sales)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-bold tabular-nums">
                                                {money(vatOutput.vat_collected)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-bold tabular-nums">
                                                {money(vatInput.total_purchases)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-bold tabular-nums">{money(vatInput.vat_paid)}</TableCell>
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
                <Notice tone="info" title="VAT calculation notes">
                    <ul className="list-inside list-disc space-y-1">
                        <li>VAT Output is calculated from paid and partially paid invoices</li>
                        <li>VAT Input is calculated from approved expenses (assuming VAT is included in expense amounts)</li>
                        <li>Standard VAT rate of 15% is applied</li>
                        <li>This report should be reviewed by your accountant before submission to Inland Revenue</li>
                    </ul>
                </Notice>
            </PageContainer>
        </AppLayout>
    );
}

function SectionRow({ className, children }: { className?: string; children: string }) {
    return (
        <TableRow className={cn('hover:bg-transparent', className)}>
            <TableCell colSpan={2} className="py-3 text-micro font-semibold tracking-label whitespace-normal text-muted-foreground uppercase">
                {children}
            </TableCell>
        </TableRow>
    );
}

function LineRow({ label, value, total = false }: { label: string; value: string; total?: boolean }) {
    return (
        <TableRow className={cn(total && 'bg-muted/50')}>
            <TableCell className={cn('whitespace-normal', total ? 'font-semibold' : 'text-muted-foreground')}>{label}</TableCell>
            <TableCell className={cn('text-right font-mono tabular-nums', total ? 'font-bold' : 'font-medium')}>{value}</TableCell>
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
        <span className={cn('font-mono tabular-nums', payableText(value))}>
            {money(Math.abs(value))}
            {value < 0 && <span className="ml-1 text-xs">(Refund)</span>}
        </span>
    );
}

function MobileFigure({ label, value, className }: { label: string; value: string; className?: string }) {
    return (
        <Fragment>
            <span className="text-muted-foreground">{label}</span>
            <span className={cn('text-right font-mono tabular-nums', className)}>{value}</span>
        </Fragment>
    );
}
