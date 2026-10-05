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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { index, store, update } from '@/routes/expenses';
import { destroy as destroyReceipt } from '@/routes/expenses/receipt';
import type { Option } from '@/types';
import { expenseCategories } from './expense-meta';

export type ExpenseFormValues = {
    category: string;
    amount: string;
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
};

type Props = {
    vehicles: Option[];
    bookings: Option[];
    /** Present when editing. */
    expense?: EditableExpense;
    defaultDate?: string;
};

const text = (value: unknown) => (value === null || value === undefined ? '' : String(value));

export function ExpenseForm({ vehicles, bookings, expense, defaultDate }: Props) {
    const editing = Boolean(expense);

    const form = useForm<ExpenseFormValues>({
        category: text(expense?.category),
        amount: text(expense?.amount),
        expense_date: expense ? text(expense.expense_date) : (defaultDate ?? ''),
        description: text(expense?.description),
        vehicle_id: text(expense?.vehicle_id),
        booking_id: text(expense?.booking_id),
        status: text(expense?.status) || 'pending',
        notes: text(expense?.notes),
        receipt_upload: null,
    });
    const { data, setData, errors, processing } = form;

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
                    description="Upload a photo or scan of the receipt (max 2MB)"
                >
                    <FileInput id="receipt_upload" accept="image/*" preview file={data.receipt_upload} onChange={(file) => setData('receipt_upload', file)} />
                </FormField>
            </FormSection>

            <FormSection title="Expense details">
                <FormField label="Category" required htmlFor="category" error={errors.category}>
                    <NativeSelect id="category" value={data.category} onChange={(e) => setData('category', e.target.value)} aria-invalid={!!errors.category}>
                        <option value="">Select category</option>
                        {expenseCategories.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

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

                <FormField label="Vehicle" htmlFor="vehicle_id" error={errors.vehicle_id} description={editing ? undefined : 'Link expense to a specific vehicle'}>
                    <NativeSelect id="vehicle_id" value={data.vehicle_id} onChange={(e) => setData('vehicle_id', e.target.value)} aria-invalid={!!errors.vehicle_id}>
                        <option value="">Select vehicle (optional)</option>
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
                    <NativeSelect id="booking_id" value={data.booking_id} onChange={(e) => setData('booking_id', e.target.value)} aria-invalid={!!errors.booking_id}>
                        <option value="">Select booking (optional)</option>
                        {bookings.map((b) => (
                            <option key={b.value} value={b.value}>
                                {b.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                <FormField label="Description" required htmlFor="description" error={errors.description} className="md:col-span-2">
                    <Textarea
                        id="description"
                        rows={3}
                        placeholder={editing ? undefined : 'Describe the expense...'}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        aria-invalid={!!errors.description}
                    />
                </FormField>
            </FormSection>


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
