import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Clock, Copy, Download, Eye, FileText, MoreHorizontal, Pencil, Plus, Search, Send, Trash2, XCircle } from 'lucide-react';
import { Fragment, useState, type ReactNode } from 'react';
import { DataPagination } from '@/components/data-pagination';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { quoteStatusTone } from '@/components/quotes/quote-status';
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
import { formatDate, formatMoney, humanize } from '@/lib/format';
import { approve, convertToBooking, create, destroy, duplicate, edit, expire, index, reject, send } from '@/routes/quotes';
import { download as pdfDownload, view as pdfView } from '@/routes/quotes/pdf';
import type { BreadcrumbItem, Paginated } from '@/types';

type QuoteRow = {
    id: number;
    quote_number: string;
    version: number;
    client_name: string | null;
    client_company: string | null;
    created_by: string | null;
    valid_until: string | null;
    valid_until_past: boolean;
    created_at: string | null;
    subtotal: number;
    total: number;
    status: string;
    has_booking: boolean;
};

type Can = { view: boolean; create: boolean; edit: boolean; delete: boolean; createBookings: boolean };

type Props = {
    quotes: Paginated<QuoteRow>;
    filters: { search: string; status: string };
    stats: { draft: number; sent: number; approved: number; rejected: number; expired: number };
    can: Can;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Quotations', href: index() }];

/** Valid-until in the past and not approved → shown as expired (as in the old view). */
const isLapsed = (quote: QuoteRow) => quote.valid_until_past && quote.status !== 'approved';

export default function QuotesIndex({ quotes, filters: initialFilters, stats, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    const [deleting, setDeleting] = useState<QuoteRow | null>(null);
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

    const actions = (quote: QuoteRow, trigger: ReactNode) => (
        <QuoteActions quote={quote} can={can} trigger={trigger} onPost={post} onDelete={() => setDeleting(quote)} />
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quotations" />
            <PageContainer>
                <PageHeader
                    title="Quotations"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New quote
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-5">
                    <StatCard label="Draft" value={stats.draft} />
                    <StatCard label="Sent" value={stats.sent} tone="info" />
                    <StatCard label="Approved" value={stats.approved} tone="positive" />
                    <StatCard label="Rejected" value={stats.rejected} tone="negative" />
                    <StatCard label="Expired" value={stats.expired} tone="warning" />
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
                                    placeholder="Search quotes or clients..."
                                />
                            </div>
                            <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)} aria-label="Filter by status">
                                <option value="">All statuses</option>
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="expired">Expired</option>
                            </NativeSelect>
                        </div>

                        {quotes.data.length === 0 ? (
                            <EmptyState icon={FileText} title="No quotes found." description="Create your first quote to get started." />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {quotes.data.map((quote) => (
                                        <div key={quote.id} className="space-y-3 rounded-lg border p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <div className="font-mono text-lg font-bold">
                                                        {can.edit ? (
                                                            <Link href={edit(quote.id)} className="underline-offset-4 hover:underline">
                                                                {quote.quote_number}
                                                            </Link>
                                                        ) : (
                                                            quote.quote_number
                                                        )}
                                                        {quote.version > 1 && <span className="text-xs font-normal text-muted-foreground"> (v{quote.version})</span>}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">{quote.created_by}</div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm text-muted-foreground">Total</div>
                                                    <div className="font-condensed text-xl font-bold tabular-nums">{formatMoney(quote.total)}</div>
                                                    <div className="text-xs text-muted-foreground">excl VAT: <span className="font-mono tabular-nums">{formatMoney(quote.subtotal)}</span></div>
                                                </div>
                                            </div>
                                            <StatusBadge tone={quoteStatusTone[quote.status] ?? 'gray'}>{humanize(quote.status)}</StatusBadge>
                                            <div className="border-t pt-3 text-sm">
                                                <div className="mb-1 text-xs text-muted-foreground">Client</div>
                                                <div className="font-medium">{quote.client_name}</div>
                                                {quote.client_company && <div className="text-xs text-muted-foreground">{quote.client_company}</div>}
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Valid until</div>
                                                    <div className={`font-mono text-sm font-medium ${isLapsed(quote) ? 'text-destructive' : ''}`}>
                                                        {formatDate(quote.valid_until, 'N/A')}
                                                    </div>
                                                    {isLapsed(quote) && <div className="text-xs text-destructive">Expired</div>}
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Created</div>
                                                    <div className="font-mono text-sm font-medium">{formatDate(quote.created_at)}</div>
                                                </div>
                                            </div>
                                            <div className="border-t pt-3">
                                                {actions(
                                                    quote,
                                                    <Button size="sm" variant="ghost" className="w-full">
                                                        <MoreHorizontal /> Actions
                                                    </Button>,
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Quote #</TableHead>
                                                <TableHead>Client</TableHead>
                                                <TableHead>Created by</TableHead>
                                                <TableHead>Valid until</TableHead>
                                                <TableHead className="text-right">Total</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {quotes.data.map((quote) => (
                                                <TableRow key={quote.id}>
                                                    <TableCell className="font-mono">
                                                        <strong>{quote.quote_number}</strong>
                                                        {quote.version > 1 && <span className="text-xs text-muted-foreground"> (v{quote.version})</span>}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="font-medium">{quote.client_name}</div>
                                                        {quote.client_company && <div className="text-sm text-muted-foreground">{quote.client_company}</div>}
                                                    </TableCell>
                                                    <TableCell className="text-sm">{quote.created_by}</TableCell>
                                                    <TableCell className="text-sm">
                                                        <span className="font-mono">{quote.valid_until ? formatDate(quote.valid_until) : ''}</span>
                                                        {isLapsed(quote) && <span className="text-xs text-destructive"> (Expired)</span>}
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono tabular-nums">
                                                        <div className="font-medium">{formatMoney(quote.total)}</div>
                                                        <div className="text-xs text-muted-foreground">excl VAT: {formatMoney(quote.subtotal)}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone={quoteStatusTone[quote.status] ?? 'gray'}>{humanize(quote.status)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {actions(
                                                            quote,
                                                            <Button size="sm" variant="ghost">
                                                                <MoreHorizontal /> Actions
                                                            </Button>,
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={quotes} />
                    </CardContent>
                </Card>
            </PageContainer>

            <AlertDialog open={deleting !== null} onOpenChange={(open) => !open && !processing && setDeleting(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>Are you sure you want to delete this quote?</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                        <Button variant="destructive" disabled={processing} onClick={confirmDelete}>
                            Delete
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}

/** The old row dropdown, with the same @can / status conditions. */
function QuoteActions({
    quote,
    can,
    trigger,
    onPost,
    onDelete,
}: {
    quote: QuoteRow;
    can: Can;
    trigger: ReactNode;
    onPost: (url: string) => void;
    onDelete: () => void;
}) {
    const groups: ReactNode[][] = [];

    if (can.view) {
        groups.push([
            <DropdownMenuItem key="view" asChild>
                <a href={pdfView(quote.id).url} target="_blank" rel="noreferrer">
                    <Eye /> View PDF
                </a>
            </DropdownMenuItem>,
            <DropdownMenuItem key="download" asChild>
                <a href={pdfDownload(quote.id).url}>
                    <Download /> Download PDF
                </a>
            </DropdownMenuItem>,
        ]);
    }

    if (can.edit) {
        const items: ReactNode[] = [];
        if (quote.status === 'draft') {
            items.push(
                <DropdownMenuItem key="send" onSelect={() => onPost(send(quote.id).url)}>
                    <Send /> Mark as sent
                </DropdownMenuItem>,
            );
        }
        if (quote.status === 'sent') {
            items.push(
                <DropdownMenuItem key="approve" onSelect={() => onPost(approve(quote.id).url)}>
                    <CheckCircle2 /> Mark as approved
                </DropdownMenuItem>,
                <DropdownMenuItem key="reject" onSelect={() => onPost(reject(quote.id).url)}>
                    <XCircle /> Mark as rejected
                </DropdownMenuItem>,
            );
        }
        if (['draft', 'sent'].includes(quote.status) && quote.valid_until_past) {
            items.push(
                <DropdownMenuItem key="expire" onSelect={() => onPost(expire(quote.id).url)}>
                    <Clock /> Mark as expired
                </DropdownMenuItem>,
            );
        }
        items.push(
            <DropdownMenuItem key="edit" asChild>
                <Link href={edit(quote.id)}>
                    <Pencil /> Edit quote
                </Link>
            </DropdownMenuItem>,
        );
        groups.push(items);
    }

    // Convert to Booking and Duplicate shared one group in the old menu.
    const tail: ReactNode[] = [];
    if (can.createBookings && quote.status === 'approved' && !quote.has_booking) {
        tail.push(
            <DropdownMenuItem key="convert" onSelect={() => onPost(convertToBooking(quote.id).url)}>
                <ArrowRight /> Convert to booking
            </DropdownMenuItem>,
        );
    }
    if (can.create) {
        tail.push(
            <DropdownMenuItem key="duplicate" onSelect={() => onPost(duplicate(quote.id).url)}>
                <Copy /> Duplicate
            </DropdownMenuItem>,
        );
    }
    if (tail.length) groups.push(tail);

    if (can.delete) {
        groups.push([
            <DropdownMenuItem key="delete" variant="destructive" onSelect={onDelete}>
                <Trash2 /> Delete quote
            </DropdownMenuItem>,
        ]);
    }

    if (groups.length === 0) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-48">
                {groups.map((group, i) => (
                    <Fragment key={i}>
                        {i > 0 && <DropdownMenuSeparator />}
                        {group}
                    </Fragment>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
