import { Head } from '@inertiajs/react';
import { MdcRateForm } from '@/components/mdc/mdc-rate-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/mdc-rates';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'MDC Rates', href: index() },
    { title: 'Add MDC Rate', href: create() },
];

export default function MdcRatesCreate({ defaultEffectiveFrom }: { defaultEffectiveFrom: string }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create MDC Rate Card" />
            <PageContainer>
                <PageHeader title="Create MDC Rate Card" description="Add a new RFANAM Mass Distance Charge rate" />
                <MdcRateForm defaultEffectiveFrom={defaultEffectiveFrom} />
            </PageContainer>
        </AppLayout>
    );
}
