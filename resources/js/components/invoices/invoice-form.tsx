import { Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { emptyLineItem, InvoiceLineItems, type InvoiceLineItem } from '@/components/invoices/invoice-line-items';
import { INVOICE_STATUSES } from '@/components/invoices/invoice-status';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/invoices';
import type { Option } from '@/types';
import { ClientSelect } from '@/components/clients/client-select';

export type ClientOption = Option & { payment_terms_days: number | null };

export type BankAccountOption = {
    id: number;
    bank_name: string;
    account_number: string;
    is_primary: boolean;
};

export type BookingOption = {
    id: number;
    label: string;
    client_id: number;
    description: string;
    items: InvoiceLineItem[];
};

export type InvoiceFormValues = {
    booking_id: string | number;
    client_id: string | number;
    company_bank_account_id: string | number;
    invoice_date: string;
    due_date: string;
    status: string;
    description: string;
    notes: string;
    items: InvoiceLineItem[];
};

type Props = {
    clients: ClientOption[];
    vehicles: Option[];
    bankAccounts: BankAccountOption[];
    taxRate: number;
    /** "Y-m-d" today, server time — payment terms are counted from it (as updatedClientId did). */
    today: string;
    initial: Partial<InvoiceFormValues>;
    /** Create only: completed bookings without an invoice. */
    bookings?: BookingOption[];
    /** Present when editing. */
    invoiceId?: number;
    /** Rendered between the line items and the notes card (edit: payment history). */
    beforeNotes?: ReactNode;
};

function addDays(ymd: string, days: number): string {
    const [y, m, d] = ymd.split('-').map(Number);
    const date = new Date(y, m - 1, d + days);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

export function InvoiceForm({ clients, vehicles, bankAccounts, taxRate, today, initial, bookings = [], invoiceId, beforeNotes }: Props) {
    const editing = invoiceId !== undefined;

    const form = useForm<InvoiceFormValues>({
        booking_id: initial.booking_id ?? '',
        client_id: initial.client_id ?? '',
        company_bank_account_id: initial.company_bank_account_id ?? '',
        invoice_date: initial.invoice_date ?? '',
        due_date: initial.due_date ?? '',
        status: initial.status ?? 'draft',
        description: initial.description ?? '',
        notes: initial.notes ?? '',
        items: initial.items && initial.items.length > 0 ? initial.items : editing ? [] : [emptyLineItem()],
    });
    const { data, setData, processing } = form;
    const errors = form.errors as Record<string, string | undefined>;

    /** updatedClientId(): due date = today + client's payment terms (create screen only). */
    const onClientChange = (value: string, client: ClientOption | undefined) => {
        if (editing) {
            setData('client_id', value);
            return;
        }
        setData((current) => ({
            ...current,
            client_id: value,
            due_date: client?.payment_terms_days ? addDays(today, client.payment_terms_days) : current.due_date,
        }));
    };

    /** loadBookingDetails(): client, description and rental/MDC lines from the booking. */
    const onBookingChange = (value: string) => {
        const booking = bookings.find((b) => String(b.id) === value);
        if (!booking) {
            setData('booking_id', value);
            return;
        }
        setData((current) => ({
            ...current,
            booking_id: value,
            client_id: booking.client_id,
            description: booking.description,
            items: booking.items.map((item) => ({ ...item })),
        }));
        toast.success('Booking details loaded successfully');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (editing) {
            form.transform(({ booking_id: _booking, ...values }) => values);
            form.put(update(invoiceId).url, { preserveScroll: true });
        } else {
            form.transform(({ status: _status, ...values }) => values);
            form.post(store().url, { preserveScroll: true });
        }
    };

    const bankAccountField = (
        <FormField
            label="Bank account"
            htmlFor="company_bank_account_id"
            error={errors.company_bank_account_id}
            description="Select which bank account details to display on this invoice"
            className="md:col-span-2"
        >
            <NativeSelect id="company_bank_account_id" value={data.company_bank_account_id ?? ''} onChange={(e) => setData('company_bank_account_id', e.target.value)}>
                <option value="">Use primary account</option>
                {bankAccounts.map((account) => (
                    <option key={account.id} value={account.id}>
                        {account.bank_name} - {account.account_number}
                        {account.is_primary ? ' (Primary)' : ''}
                    </option>
                ))}
            </NativeSelect>
        </FormField>
    );

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Invoice details" columns={editing ? 3 : 2}>
                {!editing && (
                    <FormField label="Load from completed booking (optional)" htmlFor="booking_id" error={errors.booking_id} description="Auto-populate invoice from a completed booking">
                        <NativeSelect id="booking_id" value={data.booking_id} onChange={(e) => onBookingChange(e.target.value)}>
                            <option value="">Select a completed booking...</option>
                            {bookings.map((booking) => (
                                <option key={booking.id} value={booking.id}>
                                    {booking.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                )}

                <FormField label="Client" required htmlFor="client_id" error={errors.client_id} description={editing ? undefined : 'Select the client for this invoice'}>
                    <ClientSelect clients={clients} value={data.client_id} onChange={onClientChange} invalid={!!errors.client_id} />
                </FormField>

                <FormField label="Invoice date" required htmlFor="invoice_date" error={errors.invoice_date}>
                    <Input id="invoice_date" type="date" value={data.invoice_date} onChange={(e) => setData('invoice_date', e.target.value)} aria-invalid={!!errors.invoice_date} />
                </FormField>

                <FormField label="Due date" required htmlFor="due_date" error={errors.due_date}>
                    <Input id="due_date" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} aria-invalid={!!errors.due_date} />
                </FormField>

                {editing && (
                    <FormField label="Status" required htmlFor="status" error={errors.status}>
                        <NativeSelect id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} aria-invalid={!!errors.status}>
                            {INVOICE_STATUSES.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                )}

                {bankAccountField}

                <FormField label="Description" htmlFor="description" error={errors.description} className="md:col-span-2">
                    <Textarea
                        id="description"
                        rows={2}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={editing ? undefined : 'Brief description of the invoice...'}
                    />
                </FormField>
            </FormSection>

            <InvoiceLineItems
                items={data.items}
                onChange={(items) => setData('items', items)}
                vehicles={vehicles}
                taxRate={taxRate}
                errors={errors}
                showAmountDue={!editing}
            />

            {beforeNotes}

            <FormSection title="Internal notes" columns={1}>
                <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={editing ? undefined : 'Internal notes (not visible to client)...'}
                    />
                </FormField>
            </FormSection>

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update invoice' : 'Create invoice'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}
