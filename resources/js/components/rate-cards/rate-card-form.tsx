import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { formatDateTime } from '@/lib/format';
import { index, store, update } from '@/routes/rate-cards';
import type { Option } from '@/types';

export type RateCardFormValues = {
    name: string;
    vehicle_type_id: string;
    client_id: string;
    rate_type: string;
    rate: string;
    includes_mdc: boolean;
    effective_from: string;
    effective_to: string;
    is_active: boolean;
    notes: string;
};

export type EditableRateCard = {
    id: number;
    name: string;
    vehicle_type_id: number;
    client_id: number | null;
    rate_type: string;
    rate: string | number;
    includes_mdc: boolean;
    effective_from: string | null;
    effective_to: string | null;
    is_active: boolean;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    vehicleTypes: Option[];
    clients: Option[];
    rateCard?: EditableRateCard;
    defaultEffectiveFrom?: string;
};

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

/** "04 Nov 2025, 14:30" — the old format('d M Y, H:i'). */
const dateTimeComma = (value: string | null) => formatDateTime(value).replace(/ (\d{2}:\d{2})$/, ', $1');

export function RateCardForm({ vehicleTypes, clients, rateCard, defaultEffectiveFrom }: Props) {
    const editing = Boolean(rateCard);

    const form = useForm<RateCardFormValues>({
        name: text(rateCard?.name),
        vehicle_type_id: text(rateCard?.vehicle_type_id),
        client_id: text(rateCard?.client_id),
        rate_type: text(rateCard?.rate_type),
        rate: text(rateCard?.rate),
        includes_mdc: rateCard?.includes_mdc ?? false,
        effective_from: rateCard ? text(rateCard.effective_from) : (defaultEffectiveFrom ?? ''),
        effective_to: text(rateCard?.effective_to),
        is_active: rateCard?.is_active ?? true,
        notes: text(rateCard?.notes),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (rateCard) {
            form.put(update(rateCard.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Rate Card Details">
                <FormField
                    label="Rate Card Name"
                    required
                    htmlFor="name"
                    error={errors.name}
                    className="md:col-span-2"
                    description={editing ? undefined : 'Descriptive name for this rate card'}
                >
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={editing ? undefined : 'e.g., Tipper Daily Rate - Premium Client'}
                        aria-invalid={!!errors.name}
                    />
                </FormField>

                <FormField label="Vehicle Type" required htmlFor="vehicle_type_id" error={errors.vehicle_type_id}>
                    <NativeSelect id="vehicle_type_id" value={data.vehicle_type_id} onChange={(e) => setData('vehicle_type_id', e.target.value)} aria-invalid={!!errors.vehicle_type_id}>
                        <option value="">Select vehicle type</option>
                        {vehicleTypes.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                <FormField
                    label="Client (Optional)"
                    htmlFor="client_id"
                    error={errors.client_id}
                    description={editing ? 'Client-specific rate or general rate' : 'Leave blank for general rate, or select a client for custom pricing'}
                >
                    <NativeSelect id="client_id" value={data.client_id} onChange={(e) => setData('client_id', e.target.value)} aria-invalid={!!errors.client_id}>
                        <option value="">General Rate (all clients)</option>
                        {clients.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
            </FormSection>

            <FormSection title="Pricing">
                <FormField label="Rate Type" required htmlFor="rate_type" error={errors.rate_type}>
                    <NativeSelect id="rate_type" value={data.rate_type} onChange={(e) => setData('rate_type', e.target.value)} aria-invalid={!!errors.rate_type}>
                        <option value="">Select rate type</option>
                        <option value="hourly">Hourly Rate</option>
                        <option value="daily">Daily Rate</option>
                        <option value="per_km">Per Kilometer</option>
                        <option value="tonnage">Per Ton</option>
                        <option value="load_specific">Load Specific</option>
                    </NativeSelect>
                </FormField>

                <FormField label="Rate (R)" required htmlFor="rate" error={errors.rate}>
                    <Input
                        id="rate"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder={editing ? undefined : '0.00'}
                        value={data.rate}
                        onChange={(e) => setData('rate', e.target.value)}
                        aria-invalid={!!errors.rate}
                    />
                </FormField>

                <CheckboxField
                    id="includes_mdc"
                    label="Rate includes MDC (Mass Distance Charge)"
                    checked={data.includes_mdc}
                    onChange={(checked) => setData('includes_mdc', checked)}
                    error={errors.includes_mdc}
                    description={editing ? undefined : 'Check if this rate already includes MDC calculations'}
                />
            </FormSection>

            <FormSection title="Effective Period">
                <FormField label="Effective From" required htmlFor="effective_from" error={errors.effective_from} description={editing ? undefined : 'When this rate becomes active'}>
                    <Input id="effective_from" type="date" value={data.effective_from} onChange={(e) => setData('effective_from', e.target.value)} aria-invalid={!!errors.effective_from} />
                </FormField>

                <FormField label="Effective To" htmlFor="effective_to" error={errors.effective_to} description="Leave blank for no end date">
                    <Input id="effective_to" type="date" value={data.effective_to} onChange={(e) => setData('effective_to', e.target.value)} aria-invalid={!!errors.effective_to} />
                </FormField>

                <CheckboxField
                    id="is_active"
                    label="Rate card is active"
                    checked={data.is_active}
                    onChange={(checked) => setData('is_active', checked)}
                    error={errors.is_active}
                    description={editing ? undefined : 'Inactive rate cards will not be used for pricing'}
                />
            </FormSection>

            <FormSection title="Additional Notes" columns={1}>
                <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={3}
                        placeholder={editing ? undefined : 'Any additional information about this rate card...'}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                </FormField>
            </FormSection>

            {rateCard?.created_at && (
                <FormSection title="Audit Trail">
                    <div>
                        <p className="text-sm text-muted-foreground">Created</p>
                        <p className="text-sm font-medium">{dateTimeComma(rateCard.created_at)}</p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Last Updated</p>
                        <p className="text-sm font-medium">{dateTimeComma(rateCard.updated_at)}</p>
                    </div>
                </FormSection>
            )}

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update Rate Card' : 'Create Rate Card'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}

function CheckboxField({
    id,
    label,
    checked,
    onChange,
    error,
    description,
}: {
    id: string;
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    error?: string;
    description?: string;
}) {
    return (
        <div className="grid gap-2 md:col-span-2">
            <div className="flex items-center gap-2">
                <Checkbox id={id} checked={checked} onCheckedChange={(value) => onChange(value === true)} />
                <Label htmlFor={id}>{label}</Label>
            </div>
            {description && <p className="text-xs text-muted-foreground">{description}</p>}
            <InputError message={error} />
        </div>
    );
}
