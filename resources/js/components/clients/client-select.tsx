import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/hooks/use-permissions';

export type ClientOption = { value: number | string; label: string; payment_terms_days?: number | null };

type Props<T extends ClientOption> = {
    id?: string;
    clients: T[];
    value: string | number;
    onChange: (value: string, client: T | undefined) => void;
    invalid?: boolean;
};

/**
 * The client picker on quote, invoice and booking forms. Users who may create clients get a
 * "New client" button: a short dialog creates the client without leaving the form, then adds it
 * to the list and selects it.
 */
export function ClientSelect<T extends ClientOption>({ id = 'client_id', clients, value, onChange, invalid }: Props<T>) {
    const { can } = usePermissions();
    const [created, setCreated] = useState<T[]>([]);
    const [open, setOpen] = useState(false);
    const options = [...clients, ...created.filter((c) => !clients.some((existing) => String(existing.value) === String(c.value)))].sort((a, b) =>
        a.label.localeCompare(b.label),
    );

    const select = (next: string) => onChange(next, options.find((c) => String(c.value) === next));

    return (
        <div className="flex items-end gap-2">
            <div className="min-w-0 flex-1">
                <NativeSelect id={id} value={value} onChange={(e) => select(e.target.value)} aria-invalid={invalid}>
                    <option value="">Select a client</option>
                    {options.map((client) => (
                        <option key={client.value} value={client.value}>
                            {client.label}
                        </option>
                    ))}
                </NativeSelect>
            </div>
            {can('create-clients') && (
                <>
                    <Button type="button" variant="outline" onClick={() => setOpen(true)} className="shrink-0">
                        <Plus strokeWidth={1.6} />
                        <span className="max-sm:sr-only">New client</span>
                    </Button>
                    <NewClientDialog
                        open={open}
                        onOpenChange={setOpen}
                        onCreated={(client) => {
                            setCreated((list) => [...list, client as T]);
                            onChange(String(client.value), client as T);
                        }}
                    />
                </>
            )}
        </div>
    );
}

type Fields = { name: string; company_name: string; email: string; phone: string; classification: string; payment_terms_days: string };

const EMPTY: Fields = { name: '', company_name: '', email: '', phone: '', classification: 'adhoc', payment_terms_days: '30' };

const xsrfToken = () => decodeURIComponent(document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

function NewClientDialog({ open, onOpenChange, onCreated }: { open: boolean; onOpenChange: (open: boolean) => void; onCreated: (client: ClientOption) => void }) {
    const [data, setData] = useState<Fields>(EMPTY);
    const [errors, setErrors] = useState<Partial<Record<keyof Fields, string>>>({});
    const [processing, setProcessing] = useState(false);
    const [failure, setFailure] = useState<string | null>(null);

    const set = (key: keyof Fields) => (e: { target: { value: string } }) => {
        setData((current) => ({ ...current, [key]: e.target.value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };

    const reset = (next: boolean) => {
        if (!next) {
            setData(EMPTY);
            setErrors({});
            setFailure(null);
        }
        onOpenChange(next);
    };

    // A nested form would submit the outer quote/invoice/booking form, so this one is submitted by hand.
    const submit = async (e?: FormEvent) => {
        e?.preventDefault();
        e?.stopPropagation();
        setProcessing(true);
        setFailure(null);
        try {
            const response = await fetch('/clients/quick', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
                body: JSON.stringify({ ...data, payment_terms_days: data.payment_terms_days === '' ? null : Number(data.payment_terms_days) }),
            });
            if (response.status === 422) {
                const body = await response.json();
                setErrors(Object.fromEntries(Object.entries(body.errors ?? {}).map(([key, messages]) => [key, (messages as string[])[0]])));
                return;
            }
            if (!response.ok) {
                setFailure('The client could not be saved. Try again, or add them from Clients.');
                return;
            }
            const { client } = await response.json();
            onCreated(client);
            reset(false);
        } catch {
            setFailure('No connection. Check your signal and try again.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={reset}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New client</DialogTitle>
                    <DialogDescription>Add the essentials now; the rest can be filled in later under Clients.</DialogDescription>
                </DialogHeader>

                <div
                    className="grid gap-5 sm:grid-cols-2"
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && (e.target as HTMLElement).tagName === 'INPUT') submit(e as unknown as FormEvent);
                    }}
                >
                    <FormField label="Contact name" required htmlFor="new_client_name" error={errors.name}>
                        <Input id="new_client_name" value={data.name} onChange={set('name')} autoComplete="name" autoFocus aria-invalid={!!errors.name} />
                    </FormField>
                    <FormField label="Company" htmlFor="new_client_company" error={errors.company_name}>
                        <Input id="new_client_company" value={data.company_name} onChange={set('company_name')} autoComplete="organization" />
                    </FormField>
                    <FormField label="Email" required htmlFor="new_client_email" error={errors.email}>
                        <Input id="new_client_email" type="email" value={data.email} onChange={set('email')} autoComplete="email" aria-invalid={!!errors.email} />
                    </FormField>
                    <FormField label="Phone" htmlFor="new_client_phone" error={errors.phone}>
                        <Input id="new_client_phone" type="tel" value={data.phone} onChange={set('phone')} autoComplete="tel" placeholder="+264 81 123 4567" />
                    </FormField>
                    <FormField label="Client type" htmlFor="new_client_classification" error={errors.classification}>
                        <NativeSelect id="new_client_classification" value={data.classification} onChange={set('classification')}>
                            <option value="adhoc">Ad hoc</option>
                            <option value="contract">Contract</option>
                        </NativeSelect>
                    </FormField>
                    <FormField label="Payment terms (days)" htmlFor="new_client_terms" error={errors.payment_terms_days}>
                        <Input id="new_client_terms" type="number" min={0} inputMode="numeric" value={data.payment_terms_days} onChange={set('payment_terms_days')} />
                    </FormField>
                </div>

                {failure && <p className="text-xs text-destructive">{failure}</p>}

                <DialogFooter>
                    <Button type="button" variant="ghost" onClick={() => reset(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={() => submit()} disabled={processing}>
                        {processing && <Spinner />}
                        Save client
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
