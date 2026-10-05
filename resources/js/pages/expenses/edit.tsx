import { Head } from '@inertiajs/react';
import { ExpenseForm, type EditableExpense } from '@/components/expenses/expense-form';
import { expenseStatusTone, ucfirst } from '@/components/expenses/expense-meta';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { edit, index, show } from '@/routes/expenses';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicles: Option[];
    bookings: Option[];
    expense: EditableExpense;
};

export default function ExpensesEdit({ vehicles, bookings, expense }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Expenses', href: index() },
        { title: `Expense #${expense.id}`, href: show(expense.id) },
        { title: 'Edit', href: edit(expense.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Expense" />
            <PageContainer>
                <PageHeader title="Edit Expense" actions={<StatusBadge tone={expenseStatusTone[expense.status] ?? 'gray'}>{ucfirst(expense.status)}</StatusBadge>} />
                <ExpenseForm vehicles={vehicles} bookings={bookings} expense={expense} />
            </PageContainer>
        </AppLayout>
    );
}
