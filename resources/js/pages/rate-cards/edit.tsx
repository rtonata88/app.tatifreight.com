import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { RateCardForm, type EditableRateCard } from '@/components/rate-cards/rate-card-form';
import { StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/rate-cards';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicleTypes: Option[];
    clients: Option[];
    rateCard: EditableRateCard;
};

export default function RateCardsEdit({ vehicleTypes, clients, rateCard }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Rate cards', href: index() },
        { title: rateCard.name, href: edit(rateCard.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit rate card" />
            <PageContainer>
                <PageHeader
                    title="Edit rate card"
                    actions={<StatusBadge tone={rateCard.is_active ? 'green' : 'gray'}>{rateCard.is_active ? 'Active' : 'Inactive'}</StatusBadge>}
                />
                <RateCardForm vehicleTypes={vehicleTypes} clients={clients} rateCard={rateCard} />
            </PageContainer>
        </AppLayout>
    );
}
