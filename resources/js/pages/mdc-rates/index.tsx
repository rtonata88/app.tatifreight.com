import { Head, Link, router } from '@inertiajs/react';
import { CircleCheck, CircleX, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DataPagination } from '@/components/data-pagination';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
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
import { formatDate, formatNumber } from '@/lib/format';
import { create, destroy, edit, index, toggleStatus } from '@/routes/mdc-rates';
import type { BreadcrumbItem, Paginated } from '@/types';

type RateRow = {
    id: number;
    category_name: string;
    notes: string | null;
    min_gvm_tonnes: number;
    max_gvm_tonnes: number | null;
    rate_per_100km: number;
    effective_from: string | null;
    effective_to: string | null;
    is_active: boolean;
};

type Props = {
    mdcRates: Paginated<RateRow>;
    totalRates: number;
    activeRates: number;
    filters: { search: string; status: string };
    can: { create: boolean; edit: boolean; delete: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC rates', href: index() }];

export default function MdcRatesIndex({ mdcRates, totalRates, activeRates, filters: initialFilters, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    const [deleting, setDeleting] = useState<RateRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const toggle = (rate: RateRow) => router.patch(toggleStatus(rate.id).url, {}, { preserveScroll: true });

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MDC rate cards" />
            <PageContainer>
                <PageHeader
                    title="MDC rate cards"
                    description="Manage RFANAM Mass Distance Charge rates."
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Add MDC rate
                                </Link>
                            </Button>
                        )
                    }
                />

                <Card>
                    <CardContent className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="rounded-lg bg-muted p-4">
                                <div className="text-sm text-muted-foreground">Total rate categories</div>
                                <div className="font-condensed text-2xl font-bold tabular-nums">{totalRates}</div>
                            </div>
                            <div className="rounded-lg bg-(--nx-pos-wash) p-4">
                                <div className="text-sm text-success">Active rates</div>
                                <div className="font-condensed text-2xl font-bold tabular-nums">{activeRates}</div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-4 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) => setFilter('search', e.target.value)}
                                    placeholder="Search by category or notes..."
                                />
                            </div>
                            <div className="w-full sm:w-48">
                                <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)}>
                                    <option value="all">All status</option>
                                    <option value="active">Active only</option>
                                    <option value="inactive">Inactive only</option>
                                </NativeSelect>
                            </div>
                        </div>

                        {/* Phones: cards */}
                        <div className="space-y-3 md:hidden">
                            {mdcRates.data.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    No MDC rates found. Add your first rate to get started.
                                </p>
                            ) : (
                                mdcRates.data.map((rate) => (
                                    <div key={rate.id} className="space-y-3 rounded-lg border bg-card p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="font-medium">{rate.category_name}</div>
                                                {rate.notes && <div className="text-xs text-muted-foreground">{rate.notes}</div>}
                                            </div>
                                            {rate.is_active ? (
                                                <StatusBadge tone="green">Active</StatusBadge>
                                            ) : (
                                                <StatusBadge tone="gray">Inactive</StatusBadge>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-x-4 gap-y-2 border-t pt-3">
                                            <div>
                                                <div className="text-xs text-muted-foreground">GVM range</div>
                                                <div className="text-sm tabular-nums">
                                                    {formatNumber(rate.min_gvm_tonnes, 2)}t -{' '}
                                                    {rate.max_gvm_tonnes ? `${formatNumber(rate.max_gvm_tonnes, 2)}t` : '∞'}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-xs text-muted-foreground">Rate</div>
                                                <div className="font-mono text-sm font-semibold whitespace-nowrap tabular-nums">
                                                    N$ {formatNumber(rate.rate_per_100km, 2)}
                                                    /100km
                                                </div>
                                            </div>
                                            <div className="col-span-2">
                                                <div className="text-xs text-muted-foreground">Effective period</div>
                                                <div className="text-sm">
                                                    {formatDate(rate.effective_from)} –{' '}
                                                    {rate.effective_to ? (
                                                        formatDate(rate.effective_to)
                                                    ) : (
                                                        <span className="text-success">Ongoing</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {(can.edit || can.delete) && (
                                            <div className="flex gap-2 border-t pt-3">
                                                {can.edit && (
                                                    <>
                                                        <Button asChild size="sm" variant="outline" className="flex-1">
                                                            <Link href={edit(rate.id)}>
                                                                <Pencil /> Edit
                                                            </Link>
                                                        </Button>
                                                        <Button size="sm" variant="outline" className="flex-1" onClick={() => toggle(rate)}>
                                                            {rate.is_active ? <CircleX /> : <CircleCheck />}
                                                            {rate.is_active ? 'Deactivate' : 'Activate'}
                                                        </Button>
                                                    </>
                                                )}
                                                {can.delete && (
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" aria-label="More actions">
                                                                <MoreHorizontal />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem variant="destructive" onSelect={() => setDeleting(rate)}>
                                                                <Trash2 /> Delete
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Tablets and up: table */}
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Category</TableHead>
                                        <TableHead>GVM range</TableHead>
                                        <TableHead className="text-right">Rate (N$/100km)</TableHead>
                                        <TableHead>Effective period</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {mdcRates.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                No MDC rates found. Add your first rate to get started.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        mdcRates.data.map((rate) => (
                                            <TableRow key={rate.id}>
                                                <TableCell className="whitespace-normal">
                                                    <div className="font-medium">{rate.category_name}</div>
                                                    {rate.notes && <div className="text-xs text-muted-foreground">{rate.notes}</div>}
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {formatNumber(rate.min_gvm_tonnes, 2)}t -{' '}
                                                    {rate.max_gvm_tonnes ? `${formatNumber(rate.max_gvm_tonnes, 2)}t` : '∞'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <span className="font-mono font-semibold tabular-nums">
                                                        N$ {formatNumber(rate.rate_per_100km, 2)}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    <div>From: {formatDate(rate.effective_from)}</div>
                                                    {rate.effective_to ? (
                                                        <div className="text-muted-foreground">To: {formatDate(rate.effective_to)}</div>
                                                    ) : (
                                                        <div className="text-success">Ongoing</div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {rate.is_active ? (
                                                        <StatusBadge tone="green">Active</StatusBadge>
                                                    ) : (
                                                        <StatusBadge tone="gray">Inactive</StatusBadge>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" aria-label="Actions">
                                                                <MoreHorizontal />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            {can.edit && (
                                                                <>
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={edit(rate.id)}>
                                                                            <Pencil /> Edit
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem onSelect={() => toggle(rate)}>
                                                                        {rate.is_active ? <CircleX /> : <CircleCheck />}
                                                                        {rate.is_active ? 'Deactivate' : 'Activate'}
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                            {can.delete && (
                                                                <>
                                                                    <DropdownMenuSeparator />
                                                                    <DropdownMenuItem variant="destructive" onSelect={() => setDeleting(rate)}>
                                                                        <Trash2 /> Delete
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <DataPagination paginator={mdcRates} />
                    </CardContent>
                </Card>

                {/* Opened from the row menu (a dialog cannot live inside the closing dropdown). */}
                <AlertDialog open={deleting !== null} onOpenChange={(open) => !open && !processing && setDeleting(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>Are you sure you want to delete this MDC rate?</AlertDialogDescription>
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
