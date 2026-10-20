import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FileInput } from '@/components/file-input';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { SearchableSelect } from '@/components/searchable-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDateTime, formatMoney } from '@/lib/format';
import { index, store, update } from '@/routes/expenses';
import { destroy as destroyReceipt } from '@/routes/expenses/receipt';
import type { Option } from '@/types';
import { CategorySelect } from './category-select';
import type { ExpenseCategory } from './expense-meta';

export type ExpenseFormValues = {
    category: string;
    amount: string;
    driver_id: string;
    litres: string;
    price_per_litre: string;
    odometer: string;
    /** '1' | '0' | '' until answered. */
    full_tank: string;
    station: string;
    odometer_photo_upload: File | null;
    expense_date: string;
    description: string;
    vehicle_id: string;
    booking_id: string;
    status: string;
    notes: string;
    receipt_upload: File | null;
};

export type EditableExpense = {
    id: number;
    category: string;
    amount: string | number;
    expense_date: string | null;
    description: string | null;
    vehicle_id: number | null;
    booking_id: number | null;
    notes: string | null;
    status: string;
    submitted_by: string | null;
    created_at: string | null;
    approved_by_id: number | null;
    approved_by: string | null;
    approved_at: string | null;
    receipt_path: string | null;
    receipt_url: string | null;
    driver_id: number | null;
    litres: string | number | null;
    price_per_litre: string | number | null;
    odometer: string | number | null;
    full_tank: boolean | null;
    station: string | null;
    odometer_photo_url: string | null;
};

type Props = {
    categories: ExpenseCategory[];
    vehicles: Option[];
    drivers: Option[];
    /** The signed-in driver, preselected on a new fill-up. */
    defaultDriverId?: number | null;
    /** The fuel price from Company settings: fixed on new fill-ups. Null when none is set. */
    fuelPrice?: number | null;
    bookings: Option[];
    /** Present when editing. */
    expense?: EditableExpense;
    defaultDate?: string;
};

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

export function ExpenseForm({ categories, vehicles, drivers, defaultDriverId, fuelPrice = null, bookings, expense, defaultDate }: Props) {
    const editing = Boolean(expense);

    const form = useForm<ExpenseFormValues>({
        category: text(expense?.category),
        amount: text(expense?.amount),
        driver_id: expense ? text(expense.driver_id) : text(defaultDriverId),
        litres: text(expense?.litres),
        price_per_litre: expense ? text(expense.price_per_litre) : text(fuelPrice),
        odometer: text(expense?.odometer),
        full_tank: expense?.full_tank === null || expense?.full_tank === undefined ? '' : expense.full_tank ? '1' : '0',
        station: text(expense?.station),
        odometer_photo_upload: null,
        expense_date: expense ? text(expense.expense_date) : (defaultDate ?? ''),
        description: text(expense?.description),
        vehicle_id: text(expense?.vehicle_id),
        booking_id: text(expense?.booking_id),
        status: text(expense?.status) || 'pending',
        notes: text(expense?.notes),
        receipt_upload: null,
    });
    const { data, setData, errors, processing } = form;
    const fuel = data.category === 'fuel';
    // The server records drivers as the driver of their own fill-ups; only managers pick one.
    const { can } = usePermissions();
    const canPickDriver = can('edit-expenses');
    const ownDriverName = drivers.find((d) => String(d.value) === data.driver_id)?.label;
    // New fill-ups are charged at the set price; managers editing one may correct its price.
    const priceLocked = !editing && fuelPrice !== null;
    // Matches the server: litres × price, rounded to cents.
    const fuelTotal = Number(data.litres) * Number(data.price_per_litre);
    const fuelTotalLabel = Number.isFinite(fuelTotal) && fuelTotal > 0 ? formatMoney(Math.round(fuelTotal * 1e6) / 1e6, 'N$') : '—';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (expense) {
            // Multipart forms must be sent as POST with a spoofed PUT method.
            form.transform((values) => ({ ...values, _method: 'put' }));
            form.post(update(expense.id).url, { forceFormData: true, preserveScroll: true });
        } else {
            // Status is set server-side to "pending" for new expenses.
            form.transform(({ status: _status, ...values }) => values);
            form.post(store().url, { forceFormData: true, preserveScroll: true });
        }
    };

    const decided = expense?.status === 'approved' ? 'Approved' : 'Rejected';

    return (
        <form onSubmit={submit} className="space-y-6">
            {/* Receipt first: drivers start from the slip in their hand. */}
            <FormSection title={editing ? 'Receipt' : 'Receipt upload'} columns={1}>
                {expense?.receipt_path && expense.receipt_url && (
                    <div>
                        <p className="mb-2 text-sm text-muted-foreground">Current receipt:</p>
                        <div className="flex max-w-sm flex-col items-start">
                            <img src={expense.receipt_url} alt="Receipt" className="max-w-full rounded border" />
                            <ConfirmDialog
                                trigger={
                                    <Button type="button" variant="destructive" size="sm" className="mt-2">
                                        Delete receipt
                                    </Button>
                                }
                                description="Are you sure you want to delete this receipt?"
                                onConfirm={(done) => router.delete(destroyReceipt(expense.id).url, { preserveScroll: true, preserveState: true, onFinish: done })}
                            />
                        </div>
                    </div>
                )}

                <FormField
                    label={editing ? (expense?.receipt_path ? 'Replace receipt' : 'Upload receipt') : 'Receipt/invoice image'}
                    htmlFor="receipt_upload"
                    error={errors.receipt_upload}
                    description={fuel && !editing ? 'Required for fuel: a photo of the slip (max 2MB)' : 'Upload a photo or scan of the receipt (max 2MB)'}
                    required={fuel && !editing}
                >
                    <FileInput id="receipt_upload" accept="image/*" preview file={data.receipt_upload} onChange={(file) => setData('receipt_upload', file)} />
                </FormField>
            </FormSection>

            <FormSection title="Expense details">
                <FormField label="Category" required htmlFor="category" error={errors.category}>
                    <CategorySelect categories={categories} value={data.category} onChange={(value) => setData('category', value)} invalid={!!errors.category} />
                </FormField>

                {fuel ? (
                    <FormField label="Amount (N$)" htmlFor="fuel_total" description="Litres × price per litre">
                        <p id="fuel_total" className="flex h-11 items-center border-b border-input font-mono font-medium tabular-nums md:h-9">
                            {fuelTotalLabel}
                        </p>
                    </FormField>
                ) : (
                    <FormField label="Amount (N$)" required htmlFor="amount" error={errors.amount}>
                        <Input
                            id="amount"
                            type="number"
                            inputMode="decimal"
                            step="0.01"
                            min="0.01"
                            placeholder={editing ? undefined : '0.00'}
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            aria-invalid={!!errors.amount}
                        />
                    </FormField>
                )}

                <FormField label="Expense date" required htmlFor="expense_date" error={errors.expense_date}>
                    <Input id="expense_date" type="date" value={data.expense_date} onChange={(e) => setData('expense_date', e.target.value)} aria-invalid={!!errors.expense_date} />
                </FormField>

                {editing && (
                    <FormField label="Status" required htmlFor="status" error={errors.status}>
                        <NativeSelect id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} aria-invalid={!!errors.status}>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </NativeSelect>
                    </FormField>
                )}

                <FormField
                    label="Vehicle"
                    required={fuel}
                    htmlFor="vehicle_id"
                    error={errors.vehicle_id}
                    description={editing ? undefined : fuel ? 'The vehicle that was filled up' : 'Link expense to a specific vehicle'}
                >
                    <NativeSelect id="vehicle_id" value={data.vehicle_id} onChange={(e) => setData('vehicle_id', e.target.value)} aria-invalid={!!errors.vehicle_id}>
                        <option value="">{fuel ? 'Select vehicle' : 'Select vehicle (optional)'}</option>
                        {vehicles.map((v) => (
                            <option key={v.value} value={v.value}>
                                {v.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                <FormField
                    label="Related booking"
                    htmlFor="booking_id"
                    error={errors.booking_id}
                    className={editing ? undefined : 'md:col-span-2'}
                    description={editing ? undefined : 'Link expense to a specific booking'}
                >
                    <SearchableSelect
                        id="booking_id"
                        value={data.booking_id}
                        options={bookings}
                        onChange={(value) => setData('booking_id', value)}
                        placeholder="Select booking (optional)"
                        searchPlaceholder="Search bookings…"
                        invalid={!!errors.booking_id}
                    />
                </FormField>

                <FormField
                    label="Description"
                    required={!fuel}
                    htmlFor="description"
                    error={errors.description}
                    className="md:col-span-2"
                    description={fuel ? 'Optional for fuel: left blank, it reads "Fuel: litres at station".' : undefined}
                >
                    <Textarea
                        id="description"
                        rows={3}
                        placeholder={editing ? undefined : fuel ? 'Anything the approver should know' : 'Describe the expense...'}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        aria-invalid={!!errors.description}
                    />
                </FormField>
            </FormSection>

            {fuel && (
                <FormSection title="Fill-up" description="Checked against the vehicle's fuel use and logbook when you save.">
                    <FormField label="Litres" required htmlFor="litres" error={errors.litres}>
                        <Input id="litres" type="number" inputMode="decimal" step="0.01" min="0.01" placeholder="0.00" value={data.litres} onChange={(e) => setData('litres', e.target.value)} aria-invalid={!!errors.litres} />
                    </FormField>
                    {priceLocked ? (
                        <FormField label="Price per litre (N$)" htmlFor="price_per_litre" description="Set by a manager in Company settings">
                            <p id="price_per_litre" className="flex h-11 items-center border-b border-input font-mono font-medium tabular-nums md:h-9">
                                {formatMoney(fuelPrice, 'N$')}
                            </p>
                        </FormField>
                    ) : (
                        <FormField
                            label="Price per litre (N$)"
                            required
                            htmlFor="price_per_litre"
                            error={errors.price_per_litre}
                            description={editing ? undefined : 'No fuel price is set in Company settings yet'}
                        >
                            <Input
                                id="price_per_litre"
                                type="number"
                                inputMode="decimal"
                                step="0.001"
                                min="0.01"
                                placeholder="0.000"
                                value={data.price_per_litre}
                                onChange={(e) => setData('price_per_litre', e.target.value)}
                                aria-invalid={!!errors.price_per_litre}
                            />
                        </FormField>
                    )}
                    <FormField label="Odometer at the pump (km)" required htmlFor="odometer" error={errors.odometer}>
                        <Input id="odometer" type="number" inputMode="decimal" step="0.1" min="0" value={data.odometer} onChange={(e) => setData('odometer', e.target.value)} aria-invalid={!!errors.odometer} />
                    </FormField>
                    <FormField
                        label="Did you fill the tank to full?"
                        required
                        htmlFor="full_tank"
                        error={errors.full_tank}
                        description="Full means you kept pumping until the pump clicked off. Fuel use is measured from one full tank to the next."
                    >
                        <NativeSelect id="full_tank" value={data.full_tank} onChange={(e) => setData('full_tank', e.target.value)} aria-invalid={!!errors.full_tank}>
                            <option value="">Select an answer</option>
                            <option value="1">Yes, until the pump clicked off</option>
                            <option value="0">No, only topped up (tank not full)</option>
                        </NativeSelect>
                    </FormField>
                    <FormField label="Station" required htmlFor="station" error={errors.station}>
                        <Input id="station" value={data.station} onChange={(e) => setData('station', e.target.value)} placeholder="e.g., Engen Okahandja" aria-invalid={!!errors.station} />
                    </FormField>
                    {canPickDriver ? (
                        <FormField label="Driver" htmlFor="driver_id" error={errors.driver_id}>
                            <NativeSelect id="driver_id" value={data.driver_id} onChange={(e) => setData('driver_id', e.target.value)} aria-invalid={!!errors.driver_id}>
                                <option value="">Select driver</option>
                                {drivers.map((d) => (
                                    <option key={d.value} value={d.value}>
                                        {d.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                    ) : (
                        <FormField label="Driver" htmlFor="driver_id" description="Fill-ups are recorded under your name">
                            <p id="driver_id" className="flex h-11 items-center border-b border-input font-medium md:h-9">
                                {ownDriverName ?? 'You'}
                            </p>
                        </FormField>
                    )}
                    <FormField
                        label="Dashboard odometer photo"
                        htmlFor="odometer_photo_upload"
                        error={errors.odometer_photo_upload}
                        description="Recommended: a photo of the odometer reading (max 2MB)"
                        className="md:col-span-2"
                    >
                        <FileInput
                            id="odometer_photo_upload"
                            accept="image/*"
                            preview
                            file={data.odometer_photo_upload}
                            currentUrl={expense?.odometer_photo_url}
                            onChange={(file) => setData('odometer_photo_upload', file)}
                        />
                    </FormField>
                </FormSection>
            )}


            {expense ? (
                <FormSection title="Additional information">
                    <div>
                        <p className="text-sm text-muted-foreground">Submitted by</p>
                        <p className="text-sm font-medium">{expense.submitted_by}</p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Submitted on</p>
                        <p className="text-sm font-medium">{formatDateTime(expense.created_at)}</p>
                    </div>
                    {expense.approved_by_id && (
                        <>
                            <div>
                                <p className="text-sm text-muted-foreground">{decided} by</p>
                                <p className="text-sm font-medium">{expense.approved_by}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">{decided} on</p>
                                <p className="text-sm font-medium">{formatDateTime(expense.approved_at)}</p>
                            </div>
                        </>
                    )}
                    <FormField label="Notes" htmlFor="notes" error={errors.notes} className="md:col-span-2">
                        <Textarea id="notes" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                    </FormField>
                </FormSection>
            ) : (
                <FormSection title="Additional notes" columns={1}>
                    <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                        <Textarea id="notes" rows={3} placeholder="Any additional information..." value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                    </FormField>
                </FormSection>
            )}

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update expense' : 'Submit expense'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}
