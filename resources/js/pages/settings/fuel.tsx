import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { fuel } from '@/routes/settings';
import { update } from '@/routes/settings/fuel';
import type { BreadcrumbItem } from '@/types';

/** Fuel alert thresholds (App\Services\FuelChecks). */
const THRESHOLDS = [
    ['fuel_consumption_tolerance', 'High fuel use (% over expected)', "Flag full tanks that use more than this % above the vehicle's expected L/100 km."],
    ['fuel_min_km_between_fills', 'Fills too close (km)', 'Flag a fill-up less than this many km after the previous one.'],
    ['fuel_price_tolerance', 'Price out of range (% over average)', "Flag a price per litre more than this % above the vehicle's recent fills."],
    ['fuel_trip_window_days', 'Fuel without trips (days)', 'Flag a fill-up with no logbook trip within this many days either side.'],
] as const;

type Threshold = (typeof THRESHOLDS)[number][0];

type Props = {
    settings: Record<Threshold, number> & { fuel_price_per_litre: number | null };
};

type FormValues = Record<Threshold, string> & { fuel_price_per_litre: string };

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Fuel settings', href: fuel() }];

export default function FuelSettings({ settings }: Props) {
    const { data, setData, errors, processing, put } = useForm<FormValues>({
        ...(Object.fromEntries(THRESHOLDS.map(([field]) => [field, String(settings[field])])) as Record<Threshold, string>),
        fuel_price_per_litre: settings.fuel_price_per_litre === null ? '' : String(settings.fuel_price_per_litre),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(update().url, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fuel settings" />
            <PageContainer>
                <PageHeader title="Fuel settings" description="The fuel price, and when a fill-up is flagged on the dashboard." />

                <form onSubmit={submit} className="space-y-6">
                    <FormSection title="Fuel price">
                        <FormField
                            label="Fuel price per litre (N$)"
                            htmlFor="fuel_price_per_litre"
                            error={errors.fuel_price_per_litre}
                            description="New fill-ups are charged at this price; drivers cannot change it. Update it when the pump price changes. Leave blank to let drivers enter it."
                        >
                            <Input
                                id="fuel_price_per_litre"
                                type="number"
                                step="0.001"
                                min="0.01"
                                inputMode="decimal"
                                value={data.fuel_price_per_litre}
                                onChange={(e) => setData('fuel_price_per_litre', e.target.value)}
                                placeholder="e.g., 21.450"
                                aria-invalid={!!errors.fuel_price_per_litre}
                            />
                        </FormField>
                    </FormSection>

                    <FormSection title="Fuel alerts" description="When a fill-up is flagged for review. Defaults follow the fuel monitoring spec.">
                        {THRESHOLDS.map(([field, label, help]) => (
                            <FormField key={field} label={label} htmlFor={field} error={errors[field]} description={help}>
                                <Input
                                    id={field}
                                    type="number"
                                    min={0}
                                    inputMode="numeric"
                                    value={data[field]}
                                    onChange={(e) => setData(field, e.target.value)}
                                    aria-invalid={!!errors[field]}
                                />
                            </FormField>
                        ))}
                    </FormSection>

                    <FormActions>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save settings
                        </Button>
                    </FormActions>
                </form>
            </PageContainer>
        </AppLayout>
    );
}
