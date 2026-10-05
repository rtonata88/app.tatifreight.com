import { Link, useForm } from "@inertiajs/react";
import type { FormEvent } from "react";
import { FormField } from "@/components/form-field";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Spinner } from "@/components/ui/spinner";
import { Textarea } from "@/components/ui/textarea";
import { index, store, update } from "@/routes/logbook";
import type { Option } from "@/types";

export type LogbookFormValues = {
    date: string;
    vehicle_id: string;
    driver_id: string;
    booking_id: string;
    origin_from: string;
    origin_to: string;
    start_odometer: string;
    end_odometer: string;
    purpose: string;
    notes: string;
};

export type LogbookFormOptions = {
    vehicles: Option[];
    drivers: Option[];
    bookings: Option[];
};

type Props = LogbookFormOptions & {
    /** Present when editing. */
    logbook?: { id: number } & Partial<
        Record<keyof LogbookFormValues, string | number | null>
    >;
    defaults?: Partial<LogbookFormValues>;
};

const text = (value: unknown) =>
    value === null || value === undefined ? "" : String(value);

export function LogbookForm({
    vehicles,
    drivers,
    bookings,
    logbook,
    defaults,
}: Props) {
    const form = useForm<LogbookFormValues>({
        date: text(logbook?.date ?? defaults?.date),
        vehicle_id: text(logbook?.vehicle_id),
        driver_id: text(logbook?.driver_id),
        booking_id: text(logbook?.booking_id),
        origin_from: text(logbook?.origin_from),
        origin_to: text(logbook?.origin_to),
        start_odometer: text(logbook?.start_odometer),
        end_odometer: text(logbook?.end_odometer),
        purpose: text(logbook?.purpose),
        notes: text(logbook?.notes),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (logbook) {
            form.put(update(logbook.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardContent>
                    {/* Two columns on phones too, so origin/destination and start/end odometer sit side by side. */}
                    <div className="grid grid-cols-2 gap-x-3 gap-y-6 md:gap-6">
                        <FormField
                            label="Date"
                            required
                            htmlFor="date"
                            error={errors.date}
                            className="col-span-2 md:col-span-1"
                        >
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) =>
                                    setData("date", e.target.value)
                                }
                                aria-invalid={!!errors.date}
                            />
                        </FormField>

                        <FormField
                            label="Vehicle"
                            required
                            htmlFor="vehicle_id"
                            error={errors.vehicle_id}
                            className="col-span-2 md:col-span-1"
                        >
                            <NativeSelect
                                id="vehicle_id"
                                value={data.vehicle_id}
                                onChange={(e) =>
                                    setData("vehicle_id", e.target.value)
                                }
                                aria-invalid={!!errors.vehicle_id}
                            >
                                <option value="">Select vehicle</option>
                                {vehicles.map((vehicle) => (
                                    <option
                                        key={vehicle.value}
                                        value={vehicle.value}
                                    >
                                        {vehicle.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>

                        <FormField
                            label="Driver"
                            required
                            htmlFor="driver_id"
                            error={errors.driver_id}
                            className="col-span-2 md:col-span-1"
                        >
                            <NativeSelect
                                id="driver_id"
                                value={data.driver_id}
                                onChange={(e) =>
                                    setData("driver_id", e.target.value)
                                }
                                aria-invalid={!!errors.driver_id}
                            >
                                <option value="">Select driver</option>
                                {drivers.map((driver) => (
                                    <option
                                        key={driver.value}
                                        value={driver.value}
                                    >
                                        {driver.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>

                        <FormField
                            label="Link to booking (optional)"
                            htmlFor="booking_id"
                            error={errors.booking_id}
                            className="col-span-2 md:col-span-1"
                        >
                            <NativeSelect
                                id="booking_id"
                                value={data.booking_id}
                                onChange={(e) =>
                                    setData("booking_id", e.target.value)
                                }
                                aria-invalid={!!errors.booking_id}
                            >
                                <option value="">None</option>
                                {bookings.map((booking) => (
                                    <option
                                        key={booking.value}
                                        value={booking.value}
                                    >
                                        {booking.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>

                        <FormField
                            label="Origin from"
                            required
                            htmlFor="origin_from"
                            error={errors.origin_from}
                        >
                            <Input
                                id="origin_from"
                                value={data.origin_from}
                                onChange={(e) =>
                                    setData("origin_from", e.target.value)
                                }
                                placeholder="e.g., Windhoek"
                                aria-invalid={!!errors.origin_from}
                            />
                        </FormField>

                        <FormField
                            label="Origin to"
                            required
                            htmlFor="origin_to"
                            error={errors.origin_to}
                        >
                            <Input
                                id="origin_to"
                                value={data.origin_to}
                                onChange={(e) =>
                                    setData("origin_to", e.target.value)
                                }
                                placeholder="e.g., Walvis Bay"
                                aria-invalid={!!errors.origin_to}
                            />
                        </FormField>

                        <FormField
                            label="Start odometer reading (km)"
                            required
                            htmlFor="start_odometer"
                            error={errors.start_odometer}
                            description="Starting point for trip"
                        >
                            <Input
                                id="start_odometer"
                                type="number"
                                step="0.01"
                                inputMode="decimal"
                                value={data.start_odometer}
                                onChange={(e) =>
                                    setData("start_odometer", e.target.value)
                                }
                                placeholder="e.g., 152300"
                                aria-invalid={!!errors.start_odometer}
                            />
                        </FormField>

                        <FormField
                            label="End odometer reading (km)"
                            required
                            htmlFor="end_odometer"
                            error={errors.end_odometer}
                            description="Required for MDC charge calculation"
                        >
                            <Input
                                id="end_odometer"
                                type="number"
                                step="0.01"
                                inputMode="decimal"
                                value={data.end_odometer}
                                onChange={(e) =>
                                    setData("end_odometer", e.target.value)
                                }
                                placeholder="e.g., 152780"
                                aria-invalid={!!errors.end_odometer}
                            />
                        </FormField>

                        <FormField
                            label="Purpose"
                            htmlFor="purpose"
                            error={errors.purpose}
                            className="col-span-2"
                        >
                            <Textarea
                                id="purpose"
                                rows={2}
                                value={data.purpose}
                                onChange={(e) =>
                                    setData("purpose", e.target.value)
                                }
                                placeholder="Trip purpose or description..."
                                aria-invalid={!!errors.purpose}
                            />
                        </FormField>

                        <FormField
                            label="Notes"
                            htmlFor="notes"
                            error={errors.notes}
                            className="col-span-2"
                        >
                            <Textarea
                                id="notes"
                                rows={3}
                                value={data.notes}
                                onChange={(e) =>
                                    setData("notes", e.target.value)
                                }
                                placeholder="Additional notes..."
                                aria-invalid={!!errors.notes}
                            />
                        </FormField>
                    </div>
                </CardContent>
            </Card>

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {logbook ? "Update entry" : "Create entry"}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
