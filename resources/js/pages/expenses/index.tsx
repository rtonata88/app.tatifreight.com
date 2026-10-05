import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, Clock, Download, Eye, FileText, MoreHorizontal, Pencil, Plus, ReceiptText, Search, Trash2, XCircle } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataPagination } from '@/components/data-pagination';
import { EmptyState } from '@/components/empty-state';
import { expenseCategories, expenseStatusTone, ucfirst } from '@/components/expenses/expense-meta';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import { approve, create, destroy, edit, index, reject, show } from '@/routes/expenses';
import { download } from '@/routes/expenses/receipt';
import type { BreadcrumbItem, Paginated } from '@/types';

type ExpenseRow = {
    id: number;
    expense_date: string | null;
    category: string;
    description: string | null;
    booking_number: string | null;
    vehicle: string | null;
    submitted_by: string | null;
    amount: number;
    status: string;
    approved_by: string | null;
    approved_at: string | null;
    has_receipt: boolean;
};

type Props = {
    expenses: Paginated<ExpenseRow>;
    stats: { pending: number; approved: number; rejected: number; total_pending: number; total_approved: number };
    filters: { search: string; status: string; category: string };
    can: { create: boolean; edit: boolean; delete: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Expenses', href: index() }];

const money = (value: number) => formatMoney(value, 'N$');

export default function ExpensesIndex({ expenses, stats, filters: initialFilters, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    // Desktop dropdown "Delete" opens this dialog (a dropdown item can't host the dialog trigger itself).
    const [pendingDelete, setPendingDelete] = useState<ExpenseRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const approveExpense = (expense: ExpenseRow) => router.post(approve(expense.id).url, {}, { preserveScroll: true });
    const rejectExpense = (expense: ExpenseRow) => router.post(reject(expense.id).url, {}, { preserveScroll: true });
    const deleteExpense = (expense: ExpenseRow, done: () => void) => router.delete(destroy(expense.id).url, { preserveScroll: true, onFinish: done });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Expense Management" />
            <PageContainer>
                <PageHeader
                    title="Expense Management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New Expense
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <StatCard
                        label="Pending Approval"
                        value={stats.pending}
                        hint={money(stats.total_pending)}
                        icon={Clock}
                        valueClassName="text-yellow-700 dark:text-yellow-400"
                    />
                    <StatCard
                        label="Approved"
                        value={stats.approved}
                        hint={money(stats.total_approved)}
                        icon={CheckCircle2}
                        valueClassName="text-green-700 dark:text-green-400"
                    />
                    <StatCard label="Rejected" value={stats.rejected} icon={XCircle} valueClassName="text-red-700 dark:text-red-400" />
                </div>

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input className="pl-9" value={filters.search} onChange={(e) => setFilter('search', e.target.value)} placeholder="Search expenses..." />
                            </div>
                            <NativeSelect value={filters.category} onChange={(e) => setFilter('category', e.target.value)} aria-label="Filter by category">
                                <option value="">All Categories</option>
                                {expenseCategories.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)} aria-label="Filter by status">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </NativeSelect>
                        </div>

                        {expenses.data.length === 0 ? (
                            <EmptyState icon={ReceiptText} title="No expenses found." description="Create your first expense to get started." />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {expenses.data.map((expense) => (
                                        <div key={expense.id} className="space-y-3 rounded-lg border p-4">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <div className="text-2xl font-bold">{money(expense.amount)}</div>
                                                    <div className="text-sm text-muted-foreground">{formatDate(expense.expense_date)}</div>
                                                </div>
                                                <StatusBadge tone={expenseStatusTone[expense.status] ?? 'gray'}>{ucfirst(expense.status)}</StatusBadge>
                                            </div>

                                            <div className="border-t pt-3">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex-1">
                                                        <Description expense={expense} />
                                                    </div>
                                                    <StatusBadge tone="gray">{ucfirst(expense.category)}</StatusBadge>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Vehicle</div>
                                                    {expense.vehicle ? (
                                                        <div className="text-sm font-medium">{expense.vehicle}</div>
                                                    ) : (
                                                        <div className="text-sm text-muted-foreground">-</div>
                                                    )}
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Submitted By</div>
                                                    <div className="text-sm font-medium">{expense.submitted_by}</div>
                                                </div>
                                            </div>

                                            {expense.status === 'approved' && expense.approved_by && (
                                                <div className="border-t pt-3">
                                                    <div className="text-xs text-muted-foreground">Approved By</div>
                                                    <div className="text-sm">{expense.approved_by}</div>
                                                    <div className="text-xs text-muted-foreground">{formatDateTime(expense.approved_at)}</div>
                                                </div>
                                            )}

                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                {expense.has_receipt && (
                                                    <Button asChild size="sm" variant="ghost" className="flex-1">
                                                        <a href={download(expense.id).url}>
                                                            <Download /> Download Receipt
                                                        </a>
                                                    </Button>
                                                )}
                                                {can.edit && (
                                                    <>
                                                        {expense.status === 'pending' && (
                                                            <>
                                                                <Button size="sm" className="flex-1" onClick={() => approveExpense(expense)}>
                                                                    Approve
                                                                </Button>
                                                                <Button size="sm" variant="destructive" className="flex-1" onClick={() => rejectExpense(expense)}>
                                                                    Reject
                                                                </Button>
                                                            </>
                                                        )}
                                                        <Button asChild size="sm" variant="ghost" className="flex-1">
                                                            <Link href={edit(expense.id)}>
                                                                <Pencil /> Edit
                                                            </Link>
                                                        </Button>
                                                    </>
                                                )}
                                                {can.delete && (
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button size="sm" variant="destructive">
                                                                <Trash2 /> Delete
                                                            </Button>
                                                        }
                                                        description="Are you sure you want to delete this expense?"
                                                        onConfirm={(done) => deleteExpense(expense, done)}
                                                    />
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
                                                <TableHead>Date</TableHead>
                                                <TableHead>Category</TableHead>
                                                <TableHead>Description</TableHead>
                                                <TableHead>Vehicle</TableHead>
                                                <TableHead>Submitted By</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {expenses.data.map((expense) => (
                                                <TableRow key={expense.id}>
                                                    <TableCell className="text-sm">{formatDate(expense.expense_date)}</TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone="gray">{ucfirst(expense.category)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell className="whitespace-normal">
                                                        <div className="max-w-xs">
                                                            <Description expense={expense} />
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {expense.vehicle ? <span className="text-sm">{expense.vehicle}</span> : <span className="text-sm text-muted-foreground">-</span>}
                                                    </TableCell>
                                                    <TableCell className="text-sm">{expense.submitted_by}</TableCell>
                                                    <TableCell className="font-medium">{money(expense.amount)}</TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone={expenseStatusTone[expense.status] ?? 'gray'}>{ucfirst(expense.status)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button size="icon" variant="ghost" aria-label="Actions">
                                                                    <MoreHorizontal />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="start" className="min-w-32">
                                                                {expense.has_receipt && (
                                                                    <>
                                                                        <DropdownMenuItem asChild>
                                                                            <a href={download(expense.id).url}>
                                                                                <Download /> Download Receipt
                                                                            </a>
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuSeparator />
                                                                    </>
                                                                )}
                                                                {can.edit && (
                                                                    <>
                                                                        {expense.status === 'pending' && (
                                                                            <>
                                                                                <DropdownMenuItem onSelect={() => approveExpense(expense)}>
                                                                                    <CheckCircle2 /> Approve
                                                                                </DropdownMenuItem>
                                                                                <DropdownMenuItem variant="destructive" onSelect={() => rejectExpense(expense)}>
                                                                                    <XCircle /> Reject
                                                                                </DropdownMenuItem>
                                                                                <DropdownMenuSeparator />
                                                                            </>
                                                                        )}
                                                                        <DropdownMenuItem asChild>
                                                                            <Link href={show(expense.id)}>
                                                                                <Eye /> View
                                                                            </Link>
                                                                        </DropdownMenuItem>
                                                                        <DropdownMenuItem asChild>
                                                                            <Link href={edit(expense.id)}>
                                                                                <Pencil /> Edit
                                                                            </Link>
                                                                        </DropdownMenuItem>
                                                                    </>
                                                                )}
                                                                {can.delete && (
                                                                    <>
                                                                        <DropdownMenuSeparator />
                                                                        <DropdownMenuItem variant="destructive" onSelect={() => setPendingDelete(expense)}>
                                                                            <Trash2 /> Delete
                                                                        </DropdownMenuItem>
                                                                    </>
                                                                )}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={expenses} />
                    </CardContent>
                </Card>
            </PageContainer>

            <AlertDialog open={pendingDelete !== null} onOpenChange={(open) => !open && !deleting && setPendingDelete(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>Are you sure you want to delete this expense?</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={deleting}>Cancel</AlertDialogCancel>
                        <Button
                            variant="destructive"
                            disabled={deleting}
                            onClick={() => {
                                if (!pendingDelete) return;
                                setDeleting(true);
                                deleteExpense(pendingDelete, () => {
                                    setDeleting(false);
                                    setPendingDelete(null);
                                });
                            }}
                        >
                            Delete
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}

function Description({ expense }: { expense: ExpenseRow }) {
    return (
        <>
            <div className="flex items-center gap-2">
                <p className="text-sm font-medium">{expense.description}</p>
                {expense.has_receipt && (
                    <span title="Receipt available">
                        <FileText className="size-4 shrink-0 text-green-600 dark:text-green-400" aria-label="Receipt available" />
                    </span>
                )}
            </div>
            {expense.booking_number && <p className="mt-1 text-xs text-muted-foreground">Booking: {expense.booking_number}</p>}
        </>
    );
}
