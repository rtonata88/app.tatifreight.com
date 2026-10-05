import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { RateCardForm } from '@/components/rate-cards/rate-card-form';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/rate-cards';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicleTypes: Option[];
    clients: Option[];
    defaults: { effective_from: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rate Cards', href: index() },
    { title: 'New Rate Card', href: create() },
];

export default function RateCardsCreate({ vehicleTypes, clients, defaults }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create New Rate Card" />
            <PageContainer>
                <PageHeader title="Create New Rate Card" />
                <RateCardForm vehicleTypes={vehicleTypes} clients={clients} defaultEffectiveFrom={defaults.effective_from} />
            </PageContainer>
        </AppLayout>
    );
}
