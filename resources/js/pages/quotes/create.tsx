import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import type { VehicleOption } from '@/components/quotes/line-items-editor';
import { QuoteForm, type BankAccountOption } from '@/components/quotes/quote-form';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/quotes';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    clients: Option[];
    vehicles: VehicleOption[];
    bankAccounts: BankAccountOption[];
    defaults: { valid_until: string; company_bank_account_id: number | null; terms_conditions: string };
    taxRate: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quotations', href: index() },
    { title: 'New quote', href: create() },
];

export default function QuotesCreate({ clients, vehicles, bankAccounts, defaults, taxRate }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create new quote" />
            <PageContainer>
                <PageHeader title="Create new quote" />
                <QuoteForm
                    clients={clients}
                    vehicles={vehicles}
                    bankAccounts={bankAccounts}
                    taxRate={taxRate}
                    initial={{
                        valid_until: defaults.valid_until,
                        company_bank_account_id: defaults.company_bank_account_id ?? '',
                        terms_conditions: defaults.terms_conditions,
                    }}
                />
            </PageContainer>
        </AppLayout>
    );
}
