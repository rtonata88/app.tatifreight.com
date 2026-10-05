import { Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { emptyLineItem, LineItemsEditor, type LineItem, type VehicleOption } from '@/components/quotes/line-items-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/quotes';
import type { Option } from '@/types';

export type BankAccountOption = {
    id: number;
    bank_name: string;
    account_number: string;
    is_primary: boolean;
};

export type QuoteFormValues = {
    client_id: string | number;
    company_bank_account_id: string | number;
    valid_until: string;
    status: string;
    description: string;
    terms_conditions: string;
    notes: string;
    items: LineItem[];
};

type Props = {
    clients: Option[];
    vehicles: VehicleOption[];
    taxRate: number;
    /** Create only. */
    bankAccounts?: BankAccountOption[];
    initial: Partial<QuoteFormValues>;
    /** Present when editing. */
    quoteId?: number;
    /** Rendered between the notes card and the buttons (edit timeline). */
    footer?: ReactNode;
};

export const QUOTE_STATUSES = [
    { value: 'draft', label: 'Draft' },
    { value: 'sent', label: 'Sent' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'expired', label: 'Expired' },
];

export function QuoteForm({ clients, vehicles, taxRate, bankAccounts = [], initial, quoteId, footer }: Props) {
    const editing = quoteId !== undefined;

    const form = useForm<QuoteFormValues>({
        client_id: initial.client_id ?? '',
        company_bank_account_id: initial.company_bank_account_id ?? '',
        valid_until: initial.valid_until ?? '',
        status: initial.status ?? 'draft',
        description: initial.description ?? '',
        terms_conditions: initial.terms_conditions ?? '',
        notes: initial.notes ?? '',
        items: initial.items && initial.items.length > 0 ? initial.items : editing ? [] : [emptyLineItem()],
    });
    const { data, setData, processing } = form;
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (editing) {
            form.transform(({ company_bank_account_id: _bank, ...values }) => values);
            form.put(update(quoteId).url, { preserveScroll: true });
        } else {
            form.transform(({ status: _status, ...values }) => values);
            form.post(store().url, { preserveScroll: true });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Quote Details" columns={editing ? 3 : 2}>
                <FormField label="Client" required htmlFor="client_id" error={errors.client_id} description={editing ? undefined : 'Select the client for this quote'}>
                    <NativeSelect id="client_id" value={data.client_id} onChange={(e) => setData('client_id', e.target.value)} aria-invalid={!!errors.client_id}>
                        <option value="">Select a client</option>
                        {clients.map((client) => (
                            <option key={client.value} value={client.value}>
                                {client.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>

                <FormField label="Valid Until" required htmlFor="valid_until" error={errors.valid_until} description={editing ? undefined : 'Quote expiry date'}>
                    <Input id="valid_until" type="date" value={data.valid_until} onChange={(e) => setData('valid_until', e.target.value)} aria-invalid={!!errors.valid_until} />
                </FormField>

                {editing ? (
                    <FormField label="Status" required htmlFor="status" error={errors.status}>
                        <NativeSelect id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} aria-invalid={!!errors.status}>
                            {QUOTE_STATUSES.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                ) : (
                    <FormField
                        label="Bank Account"
                        htmlFor="company_bank_account_id"
                        error={errors.company_bank_account_id}
                        description="Select which bank account details to display on this quote"
                        className="md:col-span-2"
                    >
                        <NativeSelect id="company_bank_account_id" value={data.company_bank_account_id} onChange={(e) => setData('company_bank_account_id', e.target.value)}>
                            <option value="">Use Primary Account</option>
                            {bankAccounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.bank_name} - {account.account_number}
                                    {account.is_primary ? ' (Primary)' : ''}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                )}

                <FormField label="Description" htmlFor="description" error={errors.description} className={editing ? 'md:col-span-3' : 'md:col-span-2'}>
                    <Textarea
                        id="description"
                        rows={2}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={editing ? undefined : 'Brief description of the quote...'}
                    />
                </FormField>
            </FormSection>

            <LineItemsEditor items={data.items} onChange={(items) => setData('items', items)} vehicles={vehicles} taxRate={taxRate} errors={errors} />

            <FormSection title="Terms & Conditions" columns={1}>
                <FormField label="Terms & Conditions" htmlFor="terms_conditions" error={errors.terms_conditions}>
                    <Textarea id="terms_conditions" rows={6} value={data.terms_conditions} onChange={(e) => setData('terms_conditions', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection title="Internal Notes" columns={1}>
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

            {footer}

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? 'Update Quote' : 'Create Quote'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
