import { Head } from '@inertiajs/react';
import { InvoiceForm, type BankAccountOption, type BookingOption, type ClientOption } from '@/components/invoices/invoice-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/invoices';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    clients: ClientOption[];
    bookings: BookingOption[];
    vehicles: Option[];
    bankAccounts: BankAccountOption[];
    defaults: { invoice_date: string; due_date: string; company_bank_account_id: number | null };
    today: string;
    taxRate: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: index() },
    { title: 'New Invoice', href: create() },
];

export default function InvoicesCreate({ clients, bookings, vehicles, bankAccounts, defaults, today, taxRate }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create New Invoice" />
            <PageContainer>
                <PageHeader title="Create New Invoice" />
                <InvoiceForm
                    clients={clients}
                    bookings={bookings}
                    vehicles={vehicles}
                    bankAccounts={bankAccounts}
                    taxRate={taxRate}
                    today={today}
                    initial={{
                        invoice_date: defaults.invoice_date,
                        due_date: defaults.due_date,
                        company_bank_account_id: defaults.company_bank_account_id ?? '',
                    }}
                />
            </PageContainer>
        </AppLayout>
    );
}
