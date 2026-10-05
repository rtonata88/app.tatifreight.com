import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ComponentProps, FormEvent } from 'react';
import { BankAccountDetails, type BankAccount } from '@/components/admin/bank-account-details';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FileInput } from '@/components/file-input';
import { FormActions } from '@/components/form-actions';
import { FormField } from '@/components/form-field';
import { FormSection } from '@/components/form-section';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { bankAccounts as bankAccountsRoute, company } from '@/routes/settings';
import { update } from '@/routes/settings/company';
import { destroy as destroyLogo } from '@/routes/settings/company/logo';
import { destroy as destroySignature } from '@/routes/settings/company/signature';
import type { BreadcrumbItem } from '@/types';

const TEXT_FIELDS = [
    'company_name',
    'vat_number',
    'registration_number',
    'email',
    'phone',
    'secondary_phone',
    'address',
    'city',
    'postal_code',
    'country',
    'website',
    'invoice_footer',
    'quote_footer',
    // Legacy single-account banking columns: not editable on this page (as before) but sent back unchanged.
    'bank_name',
    'bank_branch',
    'account_name',
    'account_number',
    'branch_code',
    'swift_code',
] as const;

type TextField = (typeof TEXT_FIELDS)[number];

type Props = {
    settings: Record<TextField, string | null> & { logo_url: string | null; signature_url: string | null };
    bankAccounts: BankAccount[];
};

type FormValues = Record<TextField, string> & { logo_upload: File | null; signature_upload: File | null };

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Company settings', href: company() }];

export default function CompanySettings({ settings, bankAccounts }: Props) {
    const form = useForm<FormValues>({
        ...(Object.fromEntries(TEXT_FIELDS.map((field) => [field, settings[field] ?? ''])) as Record<TextField, string>),
        logo_upload: null,
        signature_upload: null,
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // Multipart forms must be sent as POST with a spoofed PUT method.
        form.transform((values) => ({ ...values, _method: 'put' }));
        form.post(update().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.setData((current) => ({ ...current, logo_upload: null, signature_upload: null })),
        });
    };

    const text = (field: TextField, props: Omit<ComponentProps<typeof Input>, 'value' | 'onChange' | 'id'> = {}) => (
        <Input id={field} value={data[field]} onChange={(e) => setData(field, e.target.value)} aria-invalid={!!errors[field]} {...props} />
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Company settings" />
            <PageContainer>
                <PageHeader title="Company settings" description="Manage your company information, branding and document settings." />

                <form onSubmit={submit} className="space-y-6">
                    <FormSection title="Company information">
                        <FormField label="Company name" required htmlFor="company_name" error={errors.company_name}>
                            {text('company_name', { placeholder: 'TAATI Transport' })}
                        </FormField>
                        <FormField label="VAT number" htmlFor="vat_number" error={errors.vat_number} description="Your VAT registration number.">
                            {text('vat_number', { placeholder: '123456789' })}
                        </FormField>
                        <FormField
                            label="Registration number"
                            htmlFor="registration_number"
                            error={errors.registration_number}
                            description="Business registration number."
                        >
                            {text('registration_number', { placeholder: 'REG123456' })}
                        </FormField>
                        <FormField label="Email" htmlFor="email" error={errors.email}>
                            {text('email', { type: 'email', autoComplete: 'email', placeholder: 'info@taati.com.na' })}
                        </FormField>
                        <FormField label="Phone" htmlFor="phone" error={errors.phone}>
                            {text('phone', { type: 'tel', autoComplete: 'tel', placeholder: '+264 61 123 4567' })}
                        </FormField>
                        <FormField label="Secondary phone" htmlFor="secondary_phone" error={errors.secondary_phone}>
                            {text('secondary_phone', { type: 'tel', autoComplete: 'tel', placeholder: '+264 61 987 6543' })}
                        </FormField>
                        <FormField label="Website" htmlFor="website" error={errors.website}>
                            {text('website', { type: 'url', placeholder: 'https://taati.com.na' })}
                        </FormField>
                    </FormSection>

                    <FormSection title="Address">
                        <FormField label="Street address" htmlFor="address" error={errors.address} className="md:col-span-2">
                            <Textarea
                                id="address"
                                rows={2}
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                placeholder="123 Main Street, Industrial Area"
                            />
                        </FormField>
                        <FormField label="City" htmlFor="city" error={errors.city}>
                            {text('city', { placeholder: 'Windhoek' })}
                        </FormField>
                        <FormField label="Postal code" htmlFor="postal_code" error={errors.postal_code}>
                            {text('postal_code', { inputMode: 'numeric', placeholder: '9000' })}
                        </FormField>
                        <FormField label="Country" htmlFor="country" error={errors.country}>
                            {text('country', { placeholder: 'Namibia' })}
                        </FormField>
                    </FormSection>

                    <ImageUploadSection
                        id="logo_upload"
                        title="Company logo"
                        description="Upload your company logo. It appears on PDFs and documents."
                        noun="Logo"
                        help="PNG or JPG, max 2MB. A transparent background works best."
                        currentUrl={settings.logo_url}
                        imageClassName="max-h-32"
                        file={data.logo_upload}
                        error={errors.logo_upload}
                        onChange={(file) => setData('logo_upload', file)}
                        onDelete={(done) => router.delete(destroyLogo().url, { preserveScroll: true, onFinish: done })}
                    />

                    <ImageUploadSection
                        id="signature_upload"
                        title="Authorised signature"
                        description="Upload the authorised signature. It appears on invoices and official documents."
                        noun="Signature"
                        help="PNG with a transparent background, max 2MB. Signature of the authorised person."
                        currentUrl={settings.signature_url}
                        imageClassName="max-h-24 bg-white p-2"
                        file={data.signature_upload}
                        error={errors.signature_upload}
                        onChange={(file) => setData('signature_upload', file)}
                        onDelete={(done) => router.delete(destroySignature().url, { preserveScroll: true, onFinish: done })}
                    />

                    <Card>
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1.5">
                                <CardTitle>Banking details</CardTitle>
                                <CardDescription>Bank account details for payments. They appear on quotes and invoices.</CardDescription>
                            </div>
                            <Button asChild>
                                <Link href={bankAccountsRoute()}>
                                    <Plus /> Manage bank accounts
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {bankAccounts.length > 0 ? (
                                <div className="space-y-4">
                                    {bankAccounts.map((account) => (
                                        <BankAccountDetails key={account.id} account={account} compact />
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center text-muted-foreground">
                                    <p className="mb-4">No bank accounts configured yet.</p>
                                    <Button asChild>
                                        <Link href={bankAccountsRoute()}>Add your first bank account</Link>
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <FormSection title="Document footers" description="Custom footer text for quotes and invoices.">
                        <FormField label="Quote footer" htmlFor="quote_footer" error={errors.quote_footer} description="Appears at the bottom of quote PDFs.">
                            <Textarea
                                id="quote_footer"
                                rows={3}
                                value={data.quote_footer}
                                onChange={(e) => setData('quote_footer', e.target.value)}
                                placeholder="Thank you for your business."
                            />
                        </FormField>
                        <FormField
                            label="Invoice footer"
                            htmlFor="invoice_footer"
                            error={errors.invoice_footer}
                            description="Appears at the bottom of invoice PDFs."
                        >
                            <Textarea
                                id="invoice_footer"
                                rows={3}
                                value={data.invoice_footer}
                                onChange={(e) => setData('invoice_footer', e.target.value)}
                                placeholder="Payment terms: Net 30 days..."
                            />
                        </FormField>
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

function ImageUploadSection({
    id,
    title,
    description,
    noun,
    help,
    currentUrl,
    imageClassName,
    file,
    error,
    onChange,
    onDelete,
}: {
    id: string;
    title: string;
    description: string;
    noun: 'Logo' | 'Signature';
    help: string;
    currentUrl: string | null;
    imageClassName: string;
    file: File | null;
    error?: string;
    onChange: (file: File | null) => void;
    onDelete: (done: () => void) => void;
}) {
    return (
        <FormSection title={title} description={description} columns={1}>
            {currentUrl && (
                <div>
                    <p className="mb-2 text-sm text-muted-foreground">Current {noun.toLowerCase()}:</p>
                    <div className="flex flex-col items-start gap-2">
                        <img src={currentUrl} alt={`Current ${noun.toLowerCase()}`} className={`max-w-xs rounded border ${imageClassName}`} />
                        <ConfirmDialog
                            trigger={
                                <Button type="button" variant="destructive" size="sm">
                                    Delete {noun.toLowerCase()}
                                </Button>
                            }
                            description={`Are you sure you want to delete the ${noun.toLowerCase()}?`}
                            onConfirm={onDelete}
                        />
                    </div>
                </div>
            )}
            <FormField label={`${currentUrl ? 'Replace' : 'Upload'} ${noun.toLowerCase()}`} htmlFor={id} error={error} description={help}>
                <FileInput id={id} accept="image/*" preview file={file} onChange={onChange} aria-invalid={!!error} />
            </FormField>
        </FormSection>
    );
}
