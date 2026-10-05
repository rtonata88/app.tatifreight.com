import { Head } from '@inertiajs/react';
import { BookingForm, type EditableBooking, type VehicleOption } from '@/components/bookings/booking-form';
import { bookingStatusTone } from '@/components/bookings/booking-status';
import { FormSection } from '@/components/form-section';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { humanize } from '@/lib/format';
import { edit, index } from '@/routes/bookings';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    clients: Option[];
    vehicles: VehicleOption[];
    drivers: Option[];
    booking: EditableBooking & {
        booking_number: string;
        /** Already formatted as "d M Y, H:i". */
        confirmed_at: string | null;
        started_at: string | null;
        completed_at: string | null;
        cancelled_at: string | null;
    };
};

export default function BookingsEdit({ clients, vehicles, drivers, booking }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Bookings', href: index() },
        { title: booking.booking_number, href: edit(booking.id) },
    ];

    const timestamps = [
        { label: 'Confirmed at', value: booking.confirmed_at },
        { label: 'Started at', value: booking.started_at },
        { label: 'Completed at', value: booking.completed_at },
        { label: 'Cancelled at', value: booking.cancelled_at },
    ].filter((item) => item.value);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit booking: ${booking.booking_number}`} />
            <PageContainer>
                <PageHeader
                    title={`Edit booking: ${booking.booking_number}`}
                    actions={<StatusBadge tone={bookingStatusTone[booking.status] ?? 'gray'}>{humanize(booking.status)}</StatusBadge>}
                />
                <BookingForm clients={clients} vehicles={vehicles} drivers={drivers} booking={booking}>
                    {timestamps.length > 0 && (
                        <FormSection title="Status timestamps">
                            {timestamps.map((item) => (
                                <div key={item.label}>
                                    <p className="text-sm font-medium">{item.label}</p>
                                    <p className="text-sm text-muted-foreground">{item.value}</p>
                                </div>
                            ))}
                        </FormSection>
                    )}
                </BookingForm>
            </PageContainer>
        </AppLayout>
    );
}
