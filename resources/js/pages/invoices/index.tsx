import { Head, Link, router } from '@inertiajs/react';
import { CircleAlert, CircleCheck, CircleDollarSign, Download, Ellipsis, Eye, Pencil, Plus, Search, Send, SquarePen, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { invoiceStatusTone, statusLabel } from '@/components/invoices/invoice-status';
import { DataPagination } from '@/components/data-pagination';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney } from '@/lib/format';
import { create, destroy, edit, index, markPaid, send } from '@/routes/invoices';
import { download as pdfDownload, view as pdfView } from '@/routes/invoices/pdf';
import type { BreadcrumbItem, Paginated } from '@/types';

type InvoiceRow = {
    id: number;
    invoice_number: string;
    booking_number: string | null;
    client_name: string | null;
    client_company: string | null;
    invoice_date: string | null;
    due_date: string | null;
    is_overdue: boolean;
    total: number;
    amount_paid: number;
    amount_due: number;
    status: string;
};

type Can = { view: boolean; create: boolean; edit: boolean; delete: boolean };

type Props = {
    invoices: Paginated<InvoiceRow>;
    filters: { search: string; status: string };
    stats: {
        draft: number;
        sent: number;
        unpaid: number;
        partial: number;
        paid: number;
        overdue: number;
        total_unpaid: number;
        total_overdue: number;
    };
    can: Can;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Invoices', href: index() }];

const money = (value: number) => formatMoney(value, 'N$');

export default function InvoicesIndex({ invoices, filters: initialFilters, stats, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    const [deleting, setDeleting] = useState<InvoiceRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    const confirmDelete = () => {
        if (!deleting) return;
        setProcessing(true);
        router.delete(destroy(deleting.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    const actions = (invoice: InvoiceRow) => (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button size="icon" variant="ghost" className="size-8" aria-label={`Actions for ${invoice.invoice_number}`}>
                    <Ellipsis />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                {can.view && (
                    <>
                        <DropdownMenuItem asChild>
                            <a href={pdfView(invoice.id).url} target="_blank" rel="noreferrer">
                                <Eye /> View PDF
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <a href={pdfDownload(invoice.id).url}>
                                <Download /> Download PDF
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                    </>
                )}
                {can.edit && (
                    <>
                        <DropdownMenuItem asChild>
                            <Link href={edit(invoice.id)}>
                                <Pencil /> Edit
                            </Link>
                        </DropdownMenuItem>
                        {invoice.status === 'draft' && (
                            <DropdownMenuItem onSelect={() => post(send(invoice.id).url)}>
                                <Send /> Send Invoice
                            </DropdownMenuItem>
                        )}
                        {['sent', 'unpaid', 'partial', 'overdue'].includes(invoice.status) && (
                            <DropdownMenuItem onSelect={() => post(markPaid(invoice.id).url)}>
                                <CircleCheck /> Mark as Paid
                            </DropdownMenuItem>
                        )}
                        <DropdownMenuSeparator />
                    </>
                )}
                {can.delete && (
                    <DropdownMenuItem variant="destructive" onSelect={() => setDeleting(invoice)}>
                        <Trash2 /> Delete
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Invoices" />
            <PageContainer>
                <PageHeader
                    title="Invoices"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New Invoice
                                </Link>
                            </Button>
                        )
                    }
                />

                {/* Statistics cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <StatCard label="Draft" value={stats.draft} icon={SquarePen} className="bg-muted/50" />
                    <StatCard
                        label="Unpaid"
                        value={stats.unpaid}
                        hint={money(stats.total_unpaid)}
                        icon={CircleDollarSign}
                        className="bg-yellow-50 dark:bg-yellow-500/10"
                        valueClassName="text-yellow-700 dark:text-yellow-400"
                    />
                    <StatCard
                        label="Overdue"
                        value={stats.overdue}
                        hint={money(stats.total_overdue)}
                        icon={CircleAlert}
                        className="bg-red-50 dark:bg-red-500/10"
                        valueClassName="text-red-700 dark:text-red-400"
                    />
                    <StatCard
                        label="Paid"
                        value={stats.paid}
                        icon={CircleCheck}
                        className="bg-green-50 dark:bg-green-500/10"
                        valueClassName="text-green-700 dark:text-green-400"
                    />
                </div>

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) => setFilter('search', e.target.value)}
                                    placeholder="Search invoices or clients..."
                                />
                            </div>
                            <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)} aria-label="Filter by status">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </NativeSelect>
                        </div>

                        {invoices.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">No invoices found. Create your first invoice to get started.</div>
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {invoices.data.map((invoice) => (
                                        <div key={invoice.id} className="space-y-3 rounded-lg border p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <div className="font-semibold">{invoice.invoice_number}</div>
                                                    {invoice.booking_number && <div className="text-xs text-muted-foreground">{invoice.booking_number}</div>}
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <StatusBadge tone={invoiceStatusTone[invoice.status] ?? 'gray'}>{statusLabel(invoice.status)}</StatusBadge>
                                                    {actions(invoice)}
                                                </div>
                                            </div>
                                            <div className="border-t pt-3">
                                                <ClientCell invoice={invoice} />
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 border-t pt-3 text-sm">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Invoice Date</div>
                                                    {formatDate(invoice.invoice_date, '')}
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Due Date</div>
                                                    <DueDate invoice={invoice} />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Total</div>
                                                    <div className="font-medium">{money(invoice.total)}</div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Paid</div>
                                                    <PaidCell invoice={invoice} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Invoice #</TableHead>
                                                <TableHead>Client</TableHead>
                                                <TableHead>Invoice Date</TableHead>
                                                <TableHead>Due Date</TableHead>
                                                <TableHead>Total</TableHead>
                                                <TableHead>Paid</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {invoices.data.map((invoice) => (
                                                <TableRow key={invoice.id}>
                                                    <TableCell>
                                                        <strong>{invoice.invoice_number}</strong>
                                                        {invoice.booking_number && <div className="text-xs text-muted-foreground">{invoice.booking_number}</div>}
                                                    </TableCell>
                                                    <TableCell>
                                                        <ClientCell invoice={invoice} />
                                                    </TableCell>
                                                    <TableCell className="text-sm">{formatDate(invoice.invoice_date, '')}</TableCell>
                                                    <TableCell className="text-sm">
                                                        <DueDate invoice={invoice} />
                                                    </TableCell>
                                                    <TableCell className="font-medium">{money(invoice.total)}</TableCell>
                                                    <TableCell className="text-sm">
                                                        <PaidCell invoice={invoice} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone={invoiceStatusTone[invoice.status] ?? 'gray'}>{statusLabel(invoice.status)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell>{actions(invoice)}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={invoices} />
                    </CardContent>
                </Card>

                {/* wire:confirm for deleteInvoice() */}
                <AlertDialog open={deleting !== null} onOpenChange={(open) => !open && !processing && setDeleting(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>Are you sure you want to delete this invoice?</AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                            <Button variant="destructive" disabled={processing} onClick={confirmDelete}>
                                Delete
                            </Button>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </PageContainer>
        </AppLayout>
    );
}

function ClientCell({ invoice }: { invoice: InvoiceRow }) {
    return (
        <div>
            <div className="font-medium">{invoice.client_name}</div>
            {invoice.client_company && <div className="text-sm text-muted-foreground">{invoice.client_company}</div>}
        </div>
    );
}

function DueDate({ invoice }: { invoice: InvoiceRow }) {
    return (
        <span>
            {formatDate(invoice.due_date, '')}
            {invoice.is_overdue && <span className="text-xs text-red-600 dark:text-red-400"> (Overdue)</span>}
        </span>
    );
}

function PaidCell({ invoice }: { invoice: InvoiceRow }) {
    return (
        <div>
            <span className="font-medium">{money(invoice.amount_paid)}</span>
            {invoice.amount_due > 0 && <div className="text-xs text-red-600 dark:text-red-400">Due: {money(invoice.amount_due)}</div>}
        </div>
    );
}
