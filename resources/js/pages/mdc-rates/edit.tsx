import { Head } from '@inertiajs/react';
import { MdcRateForm, type MdcRateFormValues } from '@/components/mdc/mdc-rate-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/mdc-rates';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mdcRateCard: Partial<Record<keyof MdcRateFormValues, unknown>> & { id: number; category_name: string };
};

export default function MdcRatesEdit({ mdcRateCard }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'MDC Rates', href: index() },
        { title: mdcRateCard.category_name, href: edit(mdcRateCard.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit MDC Rate Card" />
            <PageContainer>
                <PageHeader title="Edit MDC Rate Card" description="Update RFANAM Mass Distance Charge rate" />
                <MdcRateForm rate={mdcRateCard} />
            </PageContainer>
        </AppLayout>
    );
}
