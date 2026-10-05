import { Head } from '@inertiajs/react';
import { BookingForm, type VehicleOption } from '@/components/bookings/booking-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/bookings';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    clients: Option[];
    vehicles: VehicleOption[];
    drivers: Option[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bookings', href: index() },
    { title: 'New booking', href: create() },
];

export default function BookingsCreate({ clients, vehicles, drivers }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create new booking" />
            <PageContainer>
                <PageHeader title="Create new booking" />
                <BookingForm clients={clients} vehicles={vehicles} drivers={drivers} />
            </PageContainer>
        </AppLayout>
    );
}
