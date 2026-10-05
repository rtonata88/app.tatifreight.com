import { Link, useForm } from "@inertiajs/react";
import type { FormEvent, ReactNode } from "react";
import { FileInput } from "@/components/file-input";
import { FormActions } from "@/components/form-actions";
import { FormField } from "@/components/form-field";
import { FormSection } from "@/components/form-section";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { NativeSelect } from "@/components/ui/native-select";
import { Spinner } from "@/components/ui/spinner";
import { Textarea } from "@/components/ui/textarea";
import { index, store, update } from "@/routes/documents";
import type { Option } from "@/types";

export const DOCUMENT_CATEGORIES: Option[] = [
    { value: "contract", label: "Contract" },
    { value: "license", label: "License" },
    { value: "insurance", label: "Insurance" },
    { value: "receipt", label: "Receipt" },
    { value: "invoice", label: "Invoice" },
    { value: "quote", label: "Quote" },
    { value: "other", label: "Other" },
];

/** The formats Document::UPLOAD_RULE accepts on the server, which also caps size at 10MB. */
export const DOCUMENT_ACCEPT = "image/jpeg,image/png,image/webp,application/pdf,.doc,.docx,.xls,.xlsx";

type Values = {
    title: string;
    category: string;
    expiry_date: string;
    description: string;
    file_upload: File | null;
    createNewVersion: boolean;
    client_id: string;
    vehicle_id: string;
    booking_id: string;
    notes: string;
};

export type EditableDocument = {
    id: number;
    title: string;
    category: string;
    expiry_date: string | null;
    description: string | null;
    client_id: number | null;
    vehicle_id: number | null;
    booking_id: number | null;
    notes: string | null;
};

type Props = {
    clients: Option[];
    vehicles: Option[];
    bookings: Option[];
    /** Present when editing. */
    document?: EditableDocument;
    /** Rendered between "Link to Records" and "Internal Notes" (edit: version history). */
    beforeNotes?: ReactNode;
};

const text = (value: unknown) =>
    value === null || value === undefined ? "" : String(value);

export function DocumentForm({
    clients,
    vehicles,
    bookings,
    document,
    beforeNotes,
}: Props) {
    const editing = Boolean(document);

    const form = useForm<Values>({
        title: text(document?.title),
        category: text(document?.category),
        expiry_date: text(document?.expiry_date),
        description: text(document?.description),
        file_upload: null,
        createNewVersion: false,
        client_id: text(document?.client_id),
        vehicle_id: text(document?.vehicle_id),
        booking_id: text(document?.booking_id),
        notes: text(document?.notes),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (document) {
            // Multipart forms must be sent as POST with a spoofed PUT method.
            form.transform((values) => ({
                ...values,
                createNewVersion: values.createNewVersion ? 1 : 0,
                _method: "put",
            }));
            form.post(update(document.id).url, {
                forceFormData: true,
                preserveScroll: true,
            });
        } else {
            form.transform(
                ({ createNewVersion: _ignored, ...values }) => values,
            );
            form.post(store().url, {
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    const ph = (value: string) => (editing ? undefined : value);

    // Picking the file first fills an empty title from its name.
    const pickFile = (file: File | null) =>
        setData((current) => ({
            ...current,
            file_upload: file,
            title:
                file && current.title.trim() === ""
                    ? file.name.replace(/\.[^.]+$/, "")
                    : current.title,
        }));

    return (
        <form onSubmit={submit} className="space-y-6">
            <FormSection
                title={editing ? "Replace file (optional)" : "File upload"}
                columns={1}
            >
                <FormField
                    label={editing ? "Upload new file" : "Select file"}
                    required={!editing}
                    htmlFor="file_upload"
                    error={errors.file_upload}
                    description={
                        editing
                            ? "Upload a new file to replace the current one"
                            : "Maximum file size: 10MB. Supported formats: PDF, Images, Word, Excel"
                    }
                >
                    <FileInput
                        id="file_upload"
                        accept={DOCUMENT_ACCEPT}
                        preview
                        file={data.file_upload}
                        onChange={pickFile}
                        aria-invalid={!!errors.file_upload}
                    />
                </FormField>

                {editing && data.file_upload && (
                    <div className="grid gap-2">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="createNewVersion"
                                checked={data.createNewVersion}
                                onCheckedChange={(checked) =>
                                    setData(
                                        "createNewVersion",
                                        checked === true,
                                    )
                                }
                            />
                            <Label htmlFor="createNewVersion">
                                Create new version (keep old file)
                            </Label>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            If checked, a new version will be created.
                            Otherwise, the current file will be replaced.
                        </p>
                    </div>
                )}
            </FormSection>

            <FormSection title="Document details">
                <FormField
                    label="Document title"
                    required
                    htmlFor="title"
                    error={errors.title}
                    className="md:col-span-2"
                >
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData("title", e.target.value)}
                        placeholder={ph("e.g., Vehicle License Disc - ABC123")}
                        aria-invalid={!!errors.title}
                    />
                </FormField>
                <FormField
                    label="Category"
                    required
                    htmlFor="category"
                    error={errors.category}
                >
                    <NativeSelect
                        id="category"
                        value={data.category}
                        onChange={(e) => setData("category", e.target.value)}
                        aria-invalid={!!errors.category}
                    >
                        <option value="">Select category</option>
                        {DOCUMENT_CATEGORIES.map((category) => (
                            <option key={category.value} value={category.value}>
                                {category.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Expiry date"
                    htmlFor="expiry_date"
                    error={errors.expiry_date}
                    description={
                        editing
                            ? undefined
                            : "Leave blank if document doesn't expire"
                    }
                >
                    <Input
                        id="expiry_date"
                        type="date"
                        value={data.expiry_date}
                        onChange={(e) => setData("expiry_date", e.target.value)}
                        aria-invalid={!!errors.expiry_date}
                    />
                </FormField>
                <FormField
                    label="Description"
                    htmlFor="description"
                    error={errors.description}
                    className="md:col-span-2"
                >
                    <Textarea
                        id="description"
                        rows={3}
                        value={data.description}
                        onChange={(e) => setData("description", e.target.value)}
                        placeholder={ph("Brief description of the document...")}
                        aria-invalid={!!errors.description}
                    />
                </FormField>
            </FormSection>


            <FormSection
                title={
                    editing ? "Link to records" : "Link to records (optional)"
                }
                columns={3}
            >
                <FormField
                    label="Client"
                    htmlFor="client_id"
                    error={errors.client_id}
                >
                    <OptionSelect
                        id="client_id"
                        value={data.client_id}
                        options={clients}
                        onChange={(value) => setData("client_id", value)}
                    />
                </FormField>
                <FormField
                    label="Vehicle"
                    htmlFor="vehicle_id"
                    error={errors.vehicle_id}
                >
                    <OptionSelect
                        id="vehicle_id"
                        value={data.vehicle_id}
                        options={vehicles}
                        onChange={(value) => setData("vehicle_id", value)}
                    />
                </FormField>
                <FormField
                    label="Booking"
                    htmlFor="booking_id"
                    error={errors.booking_id}
                >
                    <OptionSelect
                        id="booking_id"
                        value={data.booking_id}
                        options={bookings}
                        onChange={(value) => setData("booking_id", value)}
                    />
                </FormField>
            </FormSection>

            {beforeNotes}

            <FormSection title="Internal notes" columns={1}>
                <FormField label="Notes" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData("notes", e.target.value)}
                        placeholder={ph(
                            "Internal notes (not visible in document metadata)...",
                        )}
                    />
                </FormField>
            </FormSection>

            <FormActions>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {editing ? "Update document" : "Upload document"}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={index()}>Cancel</Link>
                </Button>
            </FormActions>
        </form>
    );
}

function OptionSelect({
    id,
    value,
    options,
    onChange,
}: {
    id: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <NativeSelect
            id={id}
            value={value}
            onChange={(e) => onChange(e.target.value)}
        >
            <option value="">None</option>
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </NativeSelect>
    );
}
