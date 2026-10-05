import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { calendarStatusTone, invoiceStatusTone, ucfirst } from '@/components/bookings/booking-status';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatMoney, formatNumber, humanize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/bookings';

export type CalendarBooking = {
    id: number;
    booking_number: string;
    status: string;
    /** Y-m-d, used to place the booking in the grid. */
    start_date: string;
    end_date: string;
    /** d M Y, H:i */
    start_display: string;
    end_display: string;
    duration_days: number;
    distance_km: number | null;
    pickup_location: string | null;
    notes: string | null;
    created_at: string | null;
    created_by: string | null;
    client: {
        name: string | null;
        company_name: string | null;
        phone: string | null;
        email: string | null;
    };
    vehicle: {
        reg_number: string | null;
        type: string | null;
        make: string | null;
        model: string | null;
    };
    driver: { name: string; email: string | null } | null;
    invoice: { total: number; status: string } | null;
};

function Section({ title, className, children }: { title: string; className: string; children: ReactNode }) {
    return (
        <div className={cn('rounded-lg p-4', className)}>
            <h3 className="mb-3 text-sm font-semibold">{title}</h3>
            <div className="grid grid-cols-2 gap-4 text-sm">{children}</div>
        </div>
    );
}

function Item({ label, children, wide = false }: { label: string; children: ReactNode; wide?: boolean }) {
    return (
        <div className={wide ? 'col-span-2' : undefined}>
            <span className="text-muted-foreground">{label}:</span>
            <div className="font-medium">{children}</div>
        </div>
    );
}

/** The calendar's "Booking Details" modal (old viewBooking / closeModal). */
export function BookingDetailsDialog({ booking, canEdit, onClose }: { booking: CalendarBooking | null; canEdit: boolean; onClose: () => void }) {
    return (
        <Dialog open={booking !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
                {booking && (
                    <>
                        <DialogHeader>
                            <div className="flex items-start justify-between gap-4 pr-6">
                                <div>
                                    <DialogTitle>Booking details</DialogTitle>
                                    <p className="mt-1 font-mono text-sm font-semibold">{booking.booking_number}</p>
                                    {booking.created_by && (
                                        <DialogDescription className="mt-1 text-xs">
                                            Created by {booking.created_by} on {booking.created_at}
                                        </DialogDescription>
                                    )}
                                </div>
                                <StatusBadge tone={calendarStatusTone[booking.status] ?? 'gray'}>
                                    {humanize(booking.status)}
                                </StatusBadge>
                            </div>
                        </DialogHeader>

                        <div className="space-y-6">
                            <Section title="Client information" className="bg-muted/60">
                                {booking.client.company_name && <Item label="Company">{booking.client.company_name}</Item>}
                                <Item label="Contact person">{booking.client.name}</Item>
                                {booking.client.phone && <Item label="Phone">{booking.client.phone}</Item>}
                                {booking.client.email && <Item label="Email">{booking.client.email}</Item>}
                            </Section>

                            <Section title="Vehicle information" className="bg-muted/60">
                                <Item label="Registration"><span className="font-mono">{booking.vehicle.reg_number}</span></Item>
                                <Item label="Type">{booking.vehicle.type}</Item>
                                {booking.vehicle.make && (
                                    <Item label="Make/Model">
                                        {booking.vehicle.make} {booking.vehicle.model}
                                    </Item>
                                )}
                            </Section>

                            <Section title="Trip details" className="bg-muted/60">
                                <Item label="Start date">{booking.start_display}</Item>
                                <Item label="End date">{booking.end_display}</Item>
                                <Item label="Duration">{booking.duration_days} days</Item>
                                {!!booking.distance_km && <Item label="Distance">{formatNumber(booking.distance_km, 0)} km</Item>}
                                {booking.pickup_location && (
                                    <Item label="Pickup location" wide>
                                        {booking.pickup_location}
                                    </Item>
                                )}
                            </Section>

                            {booking.driver && (
                                <Section title="Driver information" className="bg-muted/60">
                                    <Item label="Driver">{booking.driver.name}</Item>
                                    {booking.driver.email && <Item label="Email">{booking.driver.email}</Item>}
                                </Section>
                            )}

                            {booking.invoice && (
                                <Section title="Financial details" className="bg-muted/60">
                                    <div>
                                        <span className="text-muted-foreground">Invoice total:</span>
                                        <p className="font-mono text-lg font-medium tabular-nums">{formatMoney(booking.invoice.total, 'N$')}</p>
                                    </div>
                                    <Item label="Invoice status">
                                        <StatusBadge tone={invoiceStatusTone[booking.invoice.status] ?? 'gray'}>
                                            {ucfirst(booking.invoice.status)}
                                        </StatusBadge>
                                    </Item>
                                </Section>
                            )}

                            {booking.notes && (
                                <div className="rounded-lg bg-muted/60 p-4">
                                    <h3 className="mb-2 text-sm font-semibold">Notes</h3>
                                    <p className="text-sm text-muted-foreground">{booking.notes}</p>
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button variant="ghost" onClick={onClose}>
                                Close
                            </Button>
                            {canEdit && (
                                <Button asChild>
                                    <Link href={edit(booking.id)}>Edit booking</Link>
                                </Button>
                            )}
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
