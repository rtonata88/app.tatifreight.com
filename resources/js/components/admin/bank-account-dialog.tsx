import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
import type { BankAccount } from '@/components/admin/bank-account-details';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/settings/bank-accounts';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The account being edited, or null when adding. */
    account: BankAccount | null;
};

const blank = {
    bank_name: '',
    account_name: '',
    account_number: '',
    branch_name: '',
    branch_code: '',
    swift_code: '',
    currency: 'NAD',
    is_primary: false,
};

/** Add / edit bank account modal (was the Livewire $showModal form). */
export function BankAccountDialog({ open, onOpenChange, account }: Props) {
    const { data, setData, errors, processing, post, put, clearErrors } = useForm({ ...blank });

    // Load the account (or reset to blank) each time the dialog opens, like openModal() / editAccount().
    useEffect(() => {
        if (!open) return;
        clearErrors();
        setData(
            account
                ? {
                      bank_name: account.bank_name,
                      account_name: account.account_name,
                      account_number: account.account_number,
                      branch_name: account.branch_name ?? '',
                      branch_code: account.branch_code ?? '',
                      swift_code: account.swift_code ?? '',
                      currency: account.currency,
                      is_primary: account.is_primary,
                  }
                : { ...blank },
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, account?.id]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onOpenChange(false) };
        if (account) {
            put(update(account.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <form onSubmit={submit} className="space-y-6">
                    <DialogHeader>
                        <DialogTitle>{account ? 'Edit' : 'Add'} Bank Account</DialogTitle>
                        <DialogDescription className="sr-only">Bank account details shown on quotes and invoices.</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <FormField label="Bank Name" required htmlFor="bank_name" error={errors.bank_name}>
                            <Input
                                id="bank_name"
                                value={data.bank_name}
                                onChange={(e) => setData('bank_name', e.target.value)}
                                placeholder="Standard Bank Namibia"
                                aria-invalid={!!errors.bank_name}
                            />
                        </FormField>
                        <FormField label="Currency" required htmlFor="currency" error={errors.currency}>
                            <NativeSelect id="currency" value={data.currency} onChange={(e) => setData('currency', e.target.value)}>
                                <option value="NAD">NAD - Namibian Dollar</option>
                                <option value="ZAR">ZAR - South African Rand</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </NativeSelect>
                        </FormField>
                        <FormField label="Account Name" required htmlFor="account_name" error={errors.account_name} className="md:col-span-2">
                            <Input
                                id="account_name"
                                value={data.account_name}
                                onChange={(e) => setData('account_name', e.target.value)}
                                placeholder="Tati Investment CC"
                                aria-invalid={!!errors.account_name}
                            />
                        </FormField>
                        <FormField label="Account Number" required htmlFor="account_number" error={errors.account_number} className="md:col-span-2">
                            <Input
                                id="account_number"
                                value={data.account_number}
                                onChange={(e) => setData('account_number', e.target.value)}
                                placeholder="6000 6755 290"
                                aria-invalid={!!errors.account_number}
                            />
                        </FormField>
                        <FormField label="Branch Name" htmlFor="branch_name" error={errors.branch_name}>
                            <Input id="branch_name" value={data.branch_name} onChange={(e) => setData('branch_name', e.target.value)} placeholder="Katutura" />
                        </FormField>
                        <FormField label="Branch Code" htmlFor="branch_code" error={errors.branch_code}>
                            <Input id="branch_code" value={data.branch_code} onChange={(e) => setData('branch_code', e.target.value)} placeholder="082 972" />
                        </FormField>
                        <FormField
                            label="SWIFT Code"
                            htmlFor="swift_code"
                            error={errors.swift_code}
                            description="For international transfers"
                            className="md:col-span-2"
                        >
                            <Input id="swift_code" value={data.swift_code} onChange={(e) => setData('swift_code', e.target.value)} placeholder="SBNMNANX" />
                        </FormField>
                        <FormField description="Primary account will be displayed on invoices and quotes" className="md:col-span-2">
                            <div className="flex items-center gap-2">
                                <Checkbox id="is_primary" checked={data.is_primary} onCheckedChange={(checked) => setData('is_primary', checked === true)} />
                                <Label htmlFor="is_primary">Set as Primary Account</Label>
                            </div>
                        </FormField>
                    </div>

                    <DialogFooter className="sm:justify-start">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {account ? 'Update' : 'Add'} Account
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
