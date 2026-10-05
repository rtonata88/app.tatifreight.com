import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { VehicleForm, type MdcRateCardOption } from '@/components/vehicles/vehicle-form';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/vehicles';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicleTypes: Option[];
    mdcRateCards: MdcRateCardOption[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index() },
    { title: 'Add vehicle', href: create() },
];

export default function VehiclesCreate({ vehicleTypes, mdcRateCards }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add new vehicle" />
            <PageContainer>
                <PageHeader title="Add new vehicle" />
                <VehicleForm vehicleTypes={vehicleTypes} mdcRateCards={mdcRateCards} />
            </PageContainer>
        </AppLayout>
    );
}
