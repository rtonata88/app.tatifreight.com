import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FileInput } from '@/components/file-input';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/format';
import { index, store, update } from '@/routes/vehicles';
import type { Option } from '@/types';

export type MdcRateCardOption = {
    id: number;
    category_name: string;
    rate_per_100km: number;
    min_gvm_tonnes: number;
    max_gvm_tonnes: number | null;
    effective_from: string | null;
    in_effect: boolean;
};

export type VehicleFormValues = {
    vehicle_type_id: string | number;
    reg_number: string;
    vin: string;
    make: string;
    model: string;
    year: string | number;
    load_capacity: string | number;
    tare_weight: string | number;
    gvm_tonnes: string | number;
    mdc_rate_card_id: string | number;
    insurance_expiry: string;
    disc_expiry: string;
    roadworthy_expiry: string;
    status: string;
    current_mileage: string | number;
    gps_device_id: string;
    next_service_date: string;
    next_service_mileage: string | number;
    notes: string;
    license_disc_upload: File | null;
    insurance_upload: File | null;
};

type Props = {
    vehicleTypes: Option[];
    mdcRateCards: MdcRateCardOption[];
    /** Present when editing. */
    vehicle?: Partial<Record<keyof VehicleFormValues, unknown>> & {
        id: number;
        license_disc_url?: string | null;
        insurance_url?: string | null;
    };
};

/** Mirrors MdcRateCard::getActiveRateForGvm(): active, covers the GVM, in effect today, newest first. */
export function suggestRate(cards: MdcRateCardOption[], gvm: number): MdcRateCardOption | null {
    const matches = cards
        .filter((card) => card.in_effect && card.min_gvm_tonnes <= gvm && (card.max_gvm_tonnes === null || card.max_gvm_tonnes >= gvm))
        .sort((a, b) => (b.effective_from ?? '').localeCompare(a.effective_from ?? ''));

    return matches[0] ?? null;
}

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

export function VehicleForm({ vehicleTypes, mdcRateCards, vehicle }: Props) {
    const editing = Boolean(vehicle);

    const form = useForm<VehicleFormValues>({
        vehicle_type_id: text(vehicle?.vehicle_type_id),
        reg_number: text(vehicle?.reg_number),
        vin: text(vehicle?.vin),
        make: text(vehicle?.make),
        model: text(vehicle?.model),
        year: text(vehicle?.year),
        load_capacity: text(vehicle?.load_capacity),
        tare_weight: text(vehicle?.tare_weight),
        gvm_tonnes: text(vehicle?.gvm_tonnes),
        mdc_rate_card_id: text(vehicle?.mdc_rate_card_id),
        insurance_expiry: text(vehicle?.insurance_expiry),
        disc_expiry: text(vehicle?.disc_expiry),
        roadworthy_expiry: text(vehicle?.roadworthy_expiry),
        status: text(vehicle?.status) || 'available',
        current_mileage: vehicle ? text(vehicle.current_mileage) : '0',
        gps_device_id: text(vehicle?.gps_device_id),
        next_service_date: text(vehicle?.next_service_date),
        next_service_mileage: text(vehicle?.next_service_mileage),
        notes: text(vehicle?.notes),
        license_disc_upload: null,
        insurance_upload: null,
    });
    const { data, setData, errors, processing } = form;

    const gvm = parseFloat(String(data.gvm_tonnes));
    const suggested = Number.isFinite(gvm) && gvm > 0 ? suggestRate(mdcRateCards, gvm) : null;

    const onGvmChange = (value: string) => {
        const next = parseFloat(value);
        const suggestion = Number.isFinite(next) && next > 0 ? suggestRate(mdcRateCards, next) : null;
        // Auto-select the suggested rate if no rate is manually selected (as before).
        setData((current) => ({
            ...current,
            gvm_tonnes: value,
            mdc_rate_card_id: !current.mdc_rate_card_id && suggestion ? suggestion.id : current.mdc_rate_card_id,
        }));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (vehicle) {
            // Multipart forms must be sent as POST with a spoofed PUT method.
            form.transform((values) => ({ ...values, _method: 'put' }));
            form.post(update(vehicle.id).url, { forceFormData: true, preserveScroll: true });
        } else {
            form.post(store().url, { forceFormData: true, preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Basic information">
                <FormField label="Vehicle type" required htmlFor="vehicle_type_id" error={errors.vehicle_type_id}>
                    <NativeSelect id="vehicle_type_id" value={data.vehicle_type_id} onChange={(e) => setData('vehicle_type_id', e.target.value)} aria-invalid={!!errors.vehicle_type_id}>
                        <option value="">Select vehicle type</option>
                        {vehicleTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
                <FormField label="Registration number" required htmlFor="reg_number" error={errors.reg_number}>
                    <Input id="reg_number" value={data.reg_number} onChange={(e) => setData('reg_number', e.target.value)} placeholder="e.g., N 12345 W" autoCapitalize="characters" autoCorrect="off" spellCheck={false} aria-invalid={!!errors.reg_number} />
                </FormField>
                <FormField label="VIN" htmlFor="vin" error={errors.vin}>
                    <Input id="vin" value={data.vin} onChange={(e) => setData('vin', e.target.value)} placeholder="Vehicle Identification Number" autoCapitalize="characters" autoCorrect="off" spellCheck={false} aria-invalid={!!errors.vin} />
                </FormField>
                <FormField label="Make" required htmlFor="make" error={errors.make}>
                    <Input id="make" value={data.make} onChange={(e) => setData('make', e.target.value)} placeholder="e.g., Mercedes-Benz" aria-invalid={!!errors.make} />
                </FormField>
                <FormField label="Model" required htmlFor="model" error={errors.model}>
                    <Input id="model" value={data.model} onChange={(e) => setData('model', e.target.value)} placeholder="e.g., Actros" aria-invalid={!!errors.model} />
                </FormField>
                <FormField label="Year" htmlFor="year" error={errors.year}>
                    <Input id="year" type="number" inputMode="numeric" value={data.year} onChange={(e) => setData('year', e.target.value)} placeholder="e.g., 2023" aria-invalid={!!errors.year} />
                </FormField>
                <FormField label="Status" required htmlFor="status" error={errors.status}>
                    <NativeSelect id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} aria-invalid={!!errors.status}>
                        <option value="available">Available</option>
                        <option value="in_use">In use</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="retired">Retired</option>
                    </NativeSelect>
                </FormField>
                <FormField label="Current mileage (km)" htmlFor="current_mileage" error={errors.current_mileage}>
                    <Input id="current_mileage" type="number" step="0.01" inputMode="decimal" value={data.current_mileage} onFocus={(e) => e.target.select()} onChange={(e) => setData('current_mileage', e.target.value)} aria-invalid={!!errors.current_mileage} />
                </FormField>
            </FormSection>

            <FormSection title="Specifications">
                <FormField label="Load capacity (tons)" htmlFor="load_capacity" error={errors.load_capacity}>
                    <Input id="load_capacity" type="number" step="0.01" inputMode="decimal" value={data.load_capacity} onChange={(e) => setData('load_capacity', e.target.value)} />
                </FormField>
                <FormField label="Tare weight (tons)" htmlFor="tare_weight" error={errors.tare_weight}>
                    <Input id="tare_weight" type="number" step="0.01" inputMode="decimal" value={data.tare_weight} onChange={(e) => setData('tare_weight', e.target.value)} />
                </FormField>
                <FormField label="GPS Device ID" htmlFor="gps_device_id" error={errors.gps_device_id}>
                    <Input id="gps_device_id" value={data.gps_device_id} onChange={(e) => setData('gps_device_id', e.target.value)} placeholder="Optional tracking device ID" />
                </FormField>
            </FormSection>

            <FormSection title="MDC (Mass Distance Charges)" description="Configure RFANAM Mass Distance Charges for this vehicle">
                <FormField
                    label="GVM - Gross Vehicle Mass (tonnes)"
                    htmlFor="gvm_tonnes"
                    error={errors.gvm_tonnes}
                    description="The total permissible weight of the vehicle including load"
                >
                    <Input id="gvm_tonnes" type="number" step="0.01" inputMode="decimal" value={data.gvm_tonnes} onChange={(e) => onGvmChange(e.target.value)} placeholder="e.g., 34.5" />
                </FormField>
                <FormField
                    label="MDC rate card"
                    htmlFor="mdc_rate_card_id"
                    error={errors.mdc_rate_card_id}
                    description={
                        suggested
                            ? `Suggested based on GVM: ${suggested.category_name} (${formatMoney(suggested.rate_per_100km, 'N$')}/100km)`
                            : 'Enter GVM above to get rate suggestion'
                    }
                >
                    <NativeSelect id="mdc_rate_card_id" value={data.mdc_rate_card_id} onChange={(e) => setData('mdc_rate_card_id', e.target.value)}>
                        <option value="">Select MDC rate card</option>
                        {mdcRateCards.map((card) => (
                            <option key={card.id} value={card.id}>
                                {card.category_name} - {formatMoney(card.rate_per_100km, 'N$')}/100km
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
            </FormSection>

            <FormSection title="Compliance & expiry dates">
                <FormField label="Insurance expiry" htmlFor="insurance_expiry" error={errors.insurance_expiry}>
                    <Input id="insurance_expiry" type="date" value={data.insurance_expiry} onChange={(e) => setData('insurance_expiry', e.target.value)} />
                </FormField>
                <FormField label="License disc expiry" htmlFor="disc_expiry" error={errors.disc_expiry}>
                    <Input id="disc_expiry" type="date" value={data.disc_expiry} onChange={(e) => setData('disc_expiry', e.target.value)} />
                </FormField>
                <FormField label="Roadworthy expiry" htmlFor="roadworthy_expiry" error={errors.roadworthy_expiry}>
                    <Input id="roadworthy_expiry" type="date" value={data.roadworthy_expiry} onChange={(e) => setData('roadworthy_expiry', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection title="Document uploads">
                <FormField label="License disc (image)" htmlFor="license_disc_upload" error={errors.license_disc_upload}>
                    <FileInput
                        id="license_disc_upload"
                        accept="image/*"
                        preview
                        file={data.license_disc_upload}
                        currentUrl={vehicle?.license_disc_url}
                        onChange={(file) => setData('license_disc_upload', file)}
                    />
                </FormField>
                <FormField label="Insurance certificate (image)" htmlFor="insurance_upload" error={errors.insurance_upload}>
                    <FileInput
                        id="insurance_upload"
                        accept="image/*"
                        preview
                        file={data.insurance_upload}
                        currentUrl={vehicle?.insurance_url}
                        onChange={(file) => setData('insurance_upload', file)}
                    />
                </FormField>
            </FormSection>

            <FormSection title="Service schedule">
                <FormField label="Next service date" htmlFor="next_service_date" error={errors.next_service_date}>
                    <Input id="next_service_date" type="date" value={data.next_service_date} onChange={(e) => setData('next_service_date', e.target.value)} />
                </FormField>
                <FormField label="Next service mileage (km)" htmlFor="next_service_mileage" error={errors.next_service_mileage}>
                    <Input id="next_service_mileage" type="number" inputMode="numeric" value={data.next_service_mileage} onChange={(e) => setData('next_service_mileage', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection title="Additional notes" columns={1}>
                <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                    <Textarea id="notes" rows={4} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Additional information about this vehicle..." />
                </FormField>
            </FormSection>

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update vehicle' : 'Create vehicle'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}
