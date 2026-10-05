import { Link, useForm } from "@inertiajs/react";
import type { FormEvent } from "react";
import { FormField } from "@/components/form-field";
import { FormSection } from "@/components/form-section";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Spinner } from "@/components/ui/spinner";
import { Textarea } from "@/components/ui/textarea";
import { index, store, update } from "@/routes/clients";

export const REGIONS = [
    "Erongo",
    "Hardap",
    "Karas",
    "Kavango East",
    "Kavango West",
    "Khomas",
    "Kunene",
    "Ohangwena",
    "Omaheke",
    "Omusati",
    "Oshana",
    "Oshikoto",
    "Otjozondjupa",
    "Zambezi",
];

export type ClientFormValues = {
    name: string;
    company_name: string;
    email: string;
    phone: string;
    secondary_phone: string;
    tax_number: string;
    address: string;
    city: string;
    region: string;
    postal_code: string;
    classification: string;
    credit_limit: string | number;
    payment_terms_days: string | number;
    is_active: string;
    notes: string;
};

export type EditableClient = Partial<
    Record<keyof ClientFormValues, unknown>
> & { id: number; is_active?: boolean };

const text = (value: unknown) =>
    value === null || value === undefined ? "" : String(value);

export function ClientForm({ client }: { client?: EditableClient }) {
    const editing = Boolean(client);

    const form = useForm<ClientFormValues>({
        name: text(client?.name),
        company_name: text(client?.company_name),
        email: text(client?.email),
        phone: text(client?.phone),
        secondary_phone: text(client?.secondary_phone),
        tax_number: text(client?.tax_number),
        address: text(client?.address),
        city: text(client?.city),
        region: text(client?.region),
        postal_code: text(client?.postal_code),
        classification: text(client?.classification) || "adhoc",
        credit_limit: client ? text(client.credit_limit) : "0",
        payment_terms_days: client ? text(client.payment_terms_days) : "30",
        is_active: client ? (client.is_active ? "1" : "0") : "1",
        notes: text(client?.notes),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (client) {
            form.put(update(client.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    // Create screen showed example placeholders; the edit screen did not.
    const ph = (value: string) => (editing ? undefined : value);

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection title="Basic Information">
                <FormField
                    label="Contact Name"
                    required
                    htmlFor="name"
                    error={errors.name}
                >
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        placeholder={ph("John Doe")}
                        aria-invalid={!!errors.name}
                    />
                </FormField>
                <FormField
                    label="Company Name"
                    htmlFor="company_name"
                    error={errors.company_name}
                >
                    <Input
                        id="company_name"
                        value={data.company_name}
                        onChange={(e) =>
                            setData("company_name", e.target.value)
                        }
                        placeholder={ph("ABC Construction Ltd")}
                        aria-invalid={!!errors.company_name}
                    />
                </FormField>
                <FormField
                    label="Email Address"
                    required
                    htmlFor="email"
                    error={errors.email}
                >
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        placeholder={ph("client@example.com")}
                        aria-invalid={!!errors.email}
                    />
                </FormField>
                <FormField
                    label="Phone Number"
                    htmlFor="phone"
                    error={errors.phone}
                >
                    <Input
                        id="phone"
                        type="tel"
                        value={data.phone}
                        onChange={(e) => setData("phone", e.target.value)}
                        placeholder={ph("+27 12 345 6789")}
                        aria-invalid={!!errors.phone}
                    />
                </FormField>
                <FormField
                    label="Secondary Phone"
                    htmlFor="secondary_phone"
                    error={errors.secondary_phone}
                >
                    <Input
                        id="secondary_phone"
                        type="tel"
                        value={data.secondary_phone}
                        onChange={(e) =>
                            setData("secondary_phone", e.target.value)
                        }
                        placeholder={ph("+27 12 345 6789")}
                        aria-invalid={!!errors.secondary_phone}
                    />
                </FormField>
                <FormField
                    label="Tax Number (VAT)"
                    htmlFor="tax_number"
                    error={errors.tax_number}
                >
                    <Input
                        id="tax_number"
                        value={data.tax_number}
                        onChange={(e) => setData("tax_number", e.target.value)}
                        placeholder={ph("4123456789")}
                        aria-invalid={!!errors.tax_number}
                    />
                </FormField>
            </FormSection>

            <FormSection title="Address Information" columns={1}>
                <FormField
                    label="Street Address"
                    htmlFor="address"
                    error={errors.address}
                >
                    <Textarea
                        id="address"
                        rows={3}
                        value={data.address}
                        onChange={(e) => setData("address", e.target.value)}
                        placeholder={ph("123 Main Street")}
                    />
                </FormField>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <FormField label="City" htmlFor="city" error={errors.city}>
                        <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData("city", e.target.value)}
                            placeholder={ph("Johannesburg")}
                        />
                    </FormField>
                    <FormField
                        label="Region"
                        htmlFor="region"
                        error={errors.region}
                    >
                        <NativeSelect
                            id="region"
                            value={data.region}
                            onChange={(e) => setData("region", e.target.value)}
                        >
                            <option value="">Select Region</option>
                            {REGIONS.map((region) => (
                                <option key={region} value={region}>
                                    {region}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField
                        label="Postal Code"
                        htmlFor="postal_code"
                        error={errors.postal_code}
                    >
                        <Input
                            id="postal_code"
                            value={data.postal_code}
                            onChange={(e) =>
                                setData("postal_code", e.target.value)
                            }
                            placeholder={ph("2000")}
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection title="Client Classification & Terms">
                <FormField
                    label="Client Type"
                    required
                    htmlFor="classification"
                    error={errors.classification}
                    description={
                        editing
                            ? undefined
                            : "Contract clients can have custom rates and service agreements"
                    }
                >
                    <NativeSelect
                        id="classification"
                        value={data.classification}
                        onChange={(e) =>
                            setData("classification", e.target.value)
                        }
                    >
                        <option value="adhoc">Ad-hoc (Pay per booking)</option>
                        <option value="contract">
                            Contract (Long-term agreement)
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Credit Limit (N$)"
                    htmlFor="credit_limit"
                    error={errors.credit_limit}
                    description={
                        editing
                            ? undefined
                            : "Maximum outstanding amount allowed"
                    }
                >
                    <Input
                        id="credit_limit"
                        type="number"
                        step="0.01"
                        value={data.credit_limit}
                        onChange={(e) =>
                            setData("credit_limit", e.target.value)
                        }
                        placeholder={ph("0.00")}
                        aria-invalid={!!errors.credit_limit}
                    />
                </FormField>
                <FormField
                    label="Payment Terms (Days)"
                    required
                    htmlFor="payment_terms_days"
                    error={errors.payment_terms_days}
                    description={
                        editing
                            ? undefined
                            : "Number of days to pay invoices (e.g., 30, 60, 90)"
                    }
                >
                    <Input
                        id="payment_terms_days"
                        type="number"
                        value={data.payment_terms_days}
                        onChange={(e) =>
                            setData("payment_terms_days", e.target.value)
                        }
                        placeholder={ph("30")}
                        aria-invalid={!!errors.payment_terms_days}
                    />
                </FormField>
                <FormField
                    label="Status"
                    required
                    htmlFor="is_active"
                    error={errors.is_active}
                >
                    <NativeSelect
                        id="is_active"
                        value={data.is_active}
                        onChange={(e) => setData("is_active", e.target.value)}
                    >
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </NativeSelect>
                </FormField>
            </FormSection>

            <FormSection title="Additional Notes" columns={1}>
                <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={4}
                        value={data.notes}
                        onChange={(e) => setData("notes", e.target.value)}
                        placeholder={ph(
                            "Any additional information about this client...",
                        )}
                    />
                </FormField>
            </FormSection>

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? "Update Client" : "Create Client"}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
