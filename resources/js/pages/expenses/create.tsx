import { Head } from '@inertiajs/react';
import { ExpenseForm } from '@/components/expenses/expense-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/expenses';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicles: Option[];
    bookings: Option[];
    defaults: { expense_date: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: index() },
    { title: 'New Expense', href: create() },
];

export default function ExpensesCreate({ vehicles, bookings, defaults }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Submit New Expense" />
            <PageContainer>
                <PageHeader title="Submit New Expense" />
                <ExpenseForm vehicles={vehicles} bookings={bookings} defaultDate={defaults.expense_date} />
            </PageContainer>
        </AppLayout>
    );
}
