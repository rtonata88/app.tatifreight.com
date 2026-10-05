import { Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatNumber } from '@/lib/format';
import { index, store, update } from '@/routes/bookings';
import type { Option } from '@/types';
import { ClientSelect } from '@/components/clients/client-select';

export type VehicleOption = Option & {
    tare_weight: number | null;
    base_rate_per_km: number | null;
};

export type BookingFormValues = {
    client_id: string;
    vehicle_id: string;
    driver_id: string;
    status: string;
    start_date: string;
    end_date: string;
    pickup_location: string;
    delivery_location: string;
    distance_km: string;
    load_weight: string;
    cargo_description: string;
    special_instructions: string;
    notes: string;
    is_recurring: boolean;
    recurring_frequency: string;
};

export type EditableBooking = {
    id: number;
    client_id: number;
    vehicle_id: number;
    driver_id: number | null;
    status: string;
    start_date: string | null;
    end_date: string | null;
    pickup_location: string | null;
    delivery_location: string | null;
    distance_km: string | number | null;
    load_weight: string | number | null;
    cargo_description: string | null;
    special_instructions: string | null;
    notes: string | null;
    is_recurring: boolean;
    recurring_frequency: string | null;
};

type Props = {
    clients: Option[];
    vehicles: VehicleOption[];
    drivers: Option[];
    /** Present when editing. */
    booking?: EditableBooking;
    /** Extra cards shown above the buttons (the edit page's status timestamps). */
    children?: ReactNode;
};

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

export function BookingForm({ clients, vehicles, drivers, booking, children }: Props) {
    const form = useForm<BookingFormValues>({
        client_id: text(booking?.client_id),
        vehicle_id: text(booking?.vehicle_id),
        driver_id: text(booking?.driver_id),
        status: booking?.status ?? 'pending',
        start_date: text(booking?.start_date),
        end_date: text(booking?.end_date),
        pickup_location: text(booking?.pickup_location),
        delivery_location: text(booking?.delivery_location),
        distance_km: text(booking?.distance_km),
        load_weight: text(booking?.load_weight),
        cargo_description: text(booking?.cargo_description),
        special_instructions: text(booking?.special_instructions),
        notes: text(booking?.notes),
        is_recurring: Boolean(booking?.is_recurring),
        recurring_frequency: text(booking?.recurring_frequency),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (booking) {
            form.put(update(booking.id).url, { preserveScroll: true });
        } else {
            // status is fixed to pending on create; the server ignores it.
            form.transform(({ status: _status, ...values }) => values);
            form.post(store().url, { preserveScroll: true });
        }
    };

    // Old calculateEstimatedMdc(): (tare + load) × distance × base rate per km / 100.
    const calculateEstimatedMdc = () => {
        const vehicle = vehicles.find((v) => String(v.value) === data.vehicle_id);
        const distance = parseFloat(data.distance_km);
        const load = parseFloat(data.load_weight);
        if (vehicle && vehicle.tare_weight && vehicle.base_rate_per_km && distance && load) {
            const estimate = ((vehicle.tare_weight + load) * distance * vehicle.base_rate_per_km) / 100;
            toast.info(`Estimated MDC: R${formatNumber(estimate, 2)}`);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Client & vehicle selection">
                <FormField label="Client" required htmlFor="client_id" error={errors.client_id}>
                    <ClientSelect clients={clients} value={data.client_id} onChange={(value) => setData('client_id', value)} invalid={!!errors.client_id} />
                </FormField>

                <FormField
                    label="Vehicle"
                    required
                    htmlFor="vehicle_id"
                    error={errors.vehicle_id}
                    description={booking ? 'Available vehicles + current vehicle shown' : 'Only available vehicles are shown'}
                >
                    <NativeSelect
                        id="vehicle_id"
                        value={data.vehicle_id}
                        onChange={(e) => setData('vehicle_id', e.target.value)}
                        aria-invalid={!!errors.vehicle_id}
                    >
                        <option value="">Select a vehicle</option>
                        {vehicles.map((vehicle) => (
                            <option key={vehicle.value} value={vehicle.value}>
                                {vehicle.label}
                                {booking && vehicle.value === booking.vehicle_id ? ' [Current]' : ''}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                <FormField
                    label="Assign driver"
                    htmlFor="driver_id"
                    error={errors.driver_id}
                    description={booking ? undefined : 'You can assign a driver now or later'}
                >
                    <NativeSelect
                        id="driver_id"
                        value={data.driver_id}
                        onChange={(e) => setData('driver_id', e.target.value)}
                        aria-invalid={!!errors.driver_id}
                    >
                        <option value="">Select a driver (optional)</option>
                        {drivers.map((driver) => (
                            <option key={driver.value} value={driver.value}>
                                {driver.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                {booking && (
                    <FormField label="Booking status" required htmlFor="status" error={errors.status}>
                        <NativeSelect
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            aria-invalid={!!errors.status}
                        >
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="in_progress">In progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </NativeSelect>
                    </FormField>
                )}
            </FormSection>

            <FormSection title="Booking dates">
                <FormField label="Start date & time" required htmlFor="start_date" error={errors.start_date}>
                    <Input
                        id="start_date"
                        type="datetime-local"
                        value={data.start_date}
                        onChange={(e) => setData('start_date', e.target.value)}
                        aria-invalid={!!errors.start_date}
                    />
                </FormField>
                <FormField label="End date & time" required htmlFor="end_date" error={errors.end_date}>
                    <Input
                        id="end_date"
                        type="datetime-local"
                        value={data.end_date}
                        onChange={(e) => setData('end_date', e.target.value)}
                        aria-invalid={!!errors.end_date}
                    />
                </FormField>

                <FormField label="Recurring booking" error={errors.is_recurring} className="md:col-span-2">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_recurring"
                            checked={data.is_recurring}
                            onCheckedChange={(checked) => setData('is_recurring', checked === true)}
                        />
                        <Label htmlFor="is_recurring" className="font-normal">
                            This is a recurring booking
                        </Label>
                    </div>
                </FormField>

                {data.is_recurring && (
                    <FormField label="Recurring frequency" htmlFor="recurring_frequency" error={errors.recurring_frequency} className="md:col-span-2">
                        <NativeSelect
                            id="recurring_frequency"
                            value={data.recurring_frequency}
                            onChange={(e) => setData('recurring_frequency', e.target.value)}
                        >
                            <option value="">Select frequency</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </NativeSelect>
                    </FormField>
                )}
            </FormSection>

            <FormSection title="Trip details">
                <FormField label="Pickup location" htmlFor="pickup_location" error={errors.pickup_location} className="md:col-span-2">
                    <Input
                        id="pickup_location"
                        value={data.pickup_location}
                        onChange={(e) => setData('pickup_location', e.target.value)}
                        placeholder="Enter pickup address"
                        aria-invalid={!!errors.pickup_location}
                    />
                </FormField>
                <FormField label="Delivery location" htmlFor="delivery_location" error={errors.delivery_location} className="md:col-span-2">
                    <Input
                        id="delivery_location"
                        value={data.delivery_location}
                        onChange={(e) => setData('delivery_location', e.target.value)}
                        placeholder="Enter delivery address"
                        aria-invalid={!!errors.delivery_location}
                    />
                </FormField>
                <FormField label="Distance (km)" htmlFor="distance_km" error={errors.distance_km} description="Total distance for the trip">
                    <Input
                        id="distance_km"
                        type="number"
                        step="0.01"
                        inputMode="decimal"
                        value={data.distance_km}
                        onChange={(e) => setData('distance_km', e.target.value)}
                        placeholder="0.00"
                        aria-invalid={!!errors.distance_km}
                    />
                </FormField>
                <FormField
                    label="Load weight (tons)"
                    htmlFor="load_weight"
                    error={errors.load_weight}
                    description="Weight of cargo to be transported"
                >
                    <Input
                        id="load_weight"
                        type="number"
                        step="0.01"
                        inputMode="decimal"
                        value={data.load_weight}
                        onChange={(e) => setData('load_weight', e.target.value)}
                        placeholder="0.00"
                        aria-invalid={!!errors.load_weight}
                    />
                </FormField>

                {data.vehicle_id && data.distance_km && data.load_weight && (
                    <div className="flex items-center gap-2 md:col-span-2">
                        <Button type="button" variant="ghost" size="sm" onClick={calculateEstimatedMdc}>
                            Calculate estimated MDC
                        </Button>
                    </div>
                )}

                <FormField label="Cargo description" htmlFor="cargo_description" error={errors.cargo_description} className="md:col-span-2">
                    <Textarea
                        id="cargo_description"
                        rows={3}
                        value={data.cargo_description}
                        onChange={(e) => setData('cargo_description', e.target.value)}
                        placeholder="Describe the cargo being transported..."
                    />
                </FormField>
            </FormSection>

            <FormSection title="Additional information" columns={1}>
                <FormField label="Special instructions" htmlFor="special_instructions" error={errors.special_instructions}>
                    <Textarea
                        id="special_instructions"
                        rows={3}
                        value={data.special_instructions}
                        onChange={(e) => setData('special_instructions', e.target.value)}
                        placeholder="Any special handling instructions or requirements..."
                    />
                </FormField>
                <FormField label="Internal notes" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder="Internal notes (not visible to client)..."
                    />
                </FormField>
            </FormSection>

            {children}

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {booking ? 'Update booking' : 'Create booking'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}
