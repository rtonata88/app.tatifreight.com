import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { VehicleForm, type MdcRateCardOption } from '@/components/vehicles/vehicle-form';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/vehicles';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    vehicleTypes: Option[];
    mdcRateCards: MdcRateCardOption[];
    vehicle: Record<string, unknown> & {
        id: number;
        reg_number: string;
        license_disc_url: string | null;
        insurance_url: string | null;
    };
};

export default function VehiclesEdit({ vehicleTypes, mdcRateCards, vehicle }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Vehicles', href: index() },
        { title: vehicle.reg_number, href: edit(vehicle.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Vehicle: ${vehicle.reg_number}`} />
            <PageContainer>
                <PageHeader title={`Edit Vehicle: ${vehicle.reg_number}`} />
                <VehicleForm vehicleTypes={vehicleTypes} mdcRateCards={mdcRateCards} vehicle={vehicle} />
            </PageContainer>
        </AppLayout>
    );
}
