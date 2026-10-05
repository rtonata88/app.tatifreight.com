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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'MDC Rates', href: index() }];

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
            <Head title="MDC Rate Cards" />
            <PageContainer>
                <PageHeader
                    title="MDC Rate Cards"
                    description="Manage RFANAM Mass Distance Charge rates"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Add MDC Rate
                                </Link>
                            </Button>
                        )
                    }
                />

                <Card>
                    <CardContent className="space-y-6">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="rounded-lg bg-muted p-4">
                                <div className="text-sm text-muted-foreground">Total Rate Categories</div>
                                <div className="text-2xl font-bold">{totalRates}</div>
                            </div>
                            <div className="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                                <div className="text-sm text-green-600 dark:text-green-400">Active Rates</div>
                                <div className="text-2xl font-bold text-green-900 dark:text-green-100">{activeRates}</div>
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
                                    <option value="all">All Status</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                </NativeSelect>
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Category</TableHead>
                                    <TableHead>GVM Range</TableHead>
                                    <TableHead>Rate (N$/100km)</TableHead>
                                    <TableHead>Effective Period</TableHead>
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
                                            <TableCell>
                                                {formatNumber(rate.min_gvm_tonnes, 2)}t - {rate.max_gvm_tonnes ? `${formatNumber(rate.max_gvm_tonnes, 2)}t` : '∞'}
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-mono font-semibold">N$ {formatNumber(rate.rate_per_100km, 2)}</span>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                <div>From: {formatDate(rate.effective_from)}</div>
                                                {rate.effective_to ? (
                                                    <div className="text-muted-foreground">To: {formatDate(rate.effective_to)}</div>
                                                ) : (
                                                    <div className="text-green-600 dark:text-green-400">Ongoing</div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {rate.is_active ? <StatusBadge tone="green">Active</StatusBadge> : <StatusBadge tone="gray">Inactive</StatusBadge>}
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
