import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/mdc-rates';

export type MdcRateFormValues = {
    category_name: string;
    min_gvm_tonnes: string;
    max_gvm_tonnes: string;
    rate_per_100km: string;
    effective_from: string;
    effective_to: string;
    is_active: boolean;
    notes: string;
};

type Props = {
    /** Present when editing. */
    rate?: Partial<Record<keyof MdcRateFormValues, unknown>> & { id: number };
    defaultEffectiveFrom?: string;
};

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

export function MdcRateForm({ rate, defaultEffectiveFrom = '' }: Props) {
    const form = useForm<MdcRateFormValues>({
        category_name: text(rate?.category_name),
        min_gvm_tonnes: text(rate?.min_gvm_tonnes),
        max_gvm_tonnes: text(rate?.max_gvm_tonnes),
        rate_per_100km: text(rate?.rate_per_100km),
        effective_from: rate ? text(rate.effective_from) : defaultEffectiveFrom,
        effective_to: text(rate?.effective_to),
        is_active: rate ? Boolean(rate.is_active) : true,
        notes: text(rate?.notes),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (rate) {
            form.put(update(rate.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Rate Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    <FormField label="Category Name" required htmlFor="category_name" error={errors.category_name} description="Descriptive name for this GVM category">
                        <Input
                            id="category_name"
                            value={data.category_name}
                            onChange={(e) => setData('category_name', e.target.value)}
                            placeholder="e.g., Heavy Truck (24t - 27t)"
                            aria-invalid={!!errors.category_name}
                        />
                    </FormField>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField label="Minimum GVM (tonnes)" required htmlFor="min_gvm_tonnes" error={errors.min_gvm_tonnes}>
                            <Input
                                id="min_gvm_tonnes"
                                type="number"
                                step="0.01"
                                value={data.min_gvm_tonnes}
                                onChange={(e) => setData('min_gvm_tonnes', e.target.value)}
                                placeholder="0.00"
                                aria-invalid={!!errors.min_gvm_tonnes}
                            />
                        </FormField>
                        <FormField label="Maximum GVM (tonnes)" htmlFor="max_gvm_tonnes" error={errors.max_gvm_tonnes} description="Leave empty for no upper limit">
                            <Input
                                id="max_gvm_tonnes"
                                type="number"
                                step="0.01"
                                value={data.max_gvm_tonnes}
                                onChange={(e) => setData('max_gvm_tonnes', e.target.value)}
                                placeholder="Leave empty for unlimited"
                                aria-invalid={!!errors.max_gvm_tonnes}
                            />
                        </FormField>
                    </div>

                    <FormField
                        label="Rate per 100km (N$)"
                        required
                        htmlFor="rate_per_100km"
                        error={errors.rate_per_100km}
                        description="RFANAM charge rate in Namibian Dollars per 100 kilometers"
                    >
                        <Input
                            id="rate_per_100km"
                            type="number"
                            step="0.01"
                            value={data.rate_per_100km}
                            onChange={(e) => setData('rate_per_100km', e.target.value)}
                            placeholder="0.00"
                            aria-invalid={!!errors.rate_per_100km}
                        />
                    </FormField>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField label="Effective From" required htmlFor="effective_from" error={errors.effective_from}>
                            <Input
                                id="effective_from"
                                type="date"
                                value={data.effective_from}
                                onChange={(e) => setData('effective_from', e.target.value)}
                                aria-invalid={!!errors.effective_from}
                            />
                        </FormField>
                        <FormField label="Effective To" htmlFor="effective_to" error={errors.effective_to} description="Leave empty for ongoing">
                            <Input
                                id="effective_to"
                                type="date"
                                value={data.effective_to}
                                onChange={(e) => setData('effective_to', e.target.value)}
                                aria-invalid={!!errors.effective_to}
                            />
                        </FormField>
                    </div>

                    <FormField description="Only active rates will be used in MDC calculations">
                        <div className="flex items-center gap-2">
                            <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked === true)} />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                    </FormField>

                    <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                        <Textarea
                            id="notes"
                            rows={3}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Additional information about this rate category..."
                        />
                    </FormField>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-4">
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {rate ? 'Update MDC Rate' : 'Create MDC Rate'}
                </Button>
            </div>
        </form>
    );
}
