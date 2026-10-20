import { Head } from '@inertiajs/react';
import type { ExpenseCategory } from '@/components/expenses/expense-meta';
import { ExpenseForm } from '@/components/expenses/expense-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/expenses';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    categories: ExpenseCategory[];
    vehicles: Option[];
    drivers: Option[];
    fuelPrice: number | null;
    bookings: Option[];
    defaults: { expense_date: string; driver_id: number | null };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: index() },
    { title: 'New expense', href: create() },
];

export default function ExpensesCreate({ categories, vehicles, drivers, fuelPrice, bookings, defaults }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Submit new expense" />
            <PageContainer>
                <PageHeader title="Submit new expense" />
                <ExpenseForm categories={categories} vehicles={vehicles} drivers={drivers} fuelPrice={fuelPrice} bookings={bookings} defaultDate={defaults.expense_date} defaultDriverId={defaults.driver_id} />
            </PageContainer>
        </AppLayout>
    );
}
