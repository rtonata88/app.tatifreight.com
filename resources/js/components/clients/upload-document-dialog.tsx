import { useForm } from "@inertiajs/react";
import type { FormEvent } from "react";
import { FormField } from "@/components/form-field";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Spinner } from "@/components/ui/spinner";
import { Textarea } from "@/components/ui/textarea";
import { store } from "@/routes/clients/documents";

type Values = {
    uploadTitle: string;
    uploadCategory: string;
    uploadDescription: string;
    uploadFile: File | null;
};

/** The old $showUploadModal modal on the client documents screen. */
export function UploadDocumentDialog({
    clientId,
    open,
    onOpenChange,
}: {
    clientId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const form = useForm<Values>({
        uploadTitle: "",
        uploadCategory: "",
        uploadDescription: "",
        uploadFile: null,
    });
    const { data, setData, errors, processing } = form;

    const close = () => {
        form.reset();
        form.clearErrors();
        onOpenChange(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store(clientId).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                // Keep the modal open when the server reported an upload failure.
                const flash = (
                    page.props as { flash?: { error?: string | null } }
                ).flash;
                if (!flash?.error) close();
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? onOpenChange(true) : close())}
        >
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={submit} className="space-y-6">
                    <DialogHeader>
                        <DialogTitle>Upload Document</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <FormField
                            label="Document Title"
                            required
                            htmlFor="uploadTitle"
                            error={errors.uploadTitle}
                        >
                            <Input
                                id="uploadTitle"
                                value={data.uploadTitle}
                                onChange={(e) =>
                                    setData("uploadTitle", e.target.value)
                                }
                                placeholder="e.g., Service Agreement 2024"
                                aria-invalid={!!errors.uploadTitle}
                            />
                        </FormField>
                        <FormField
                            label="Category"
                            required
                            htmlFor="uploadCategory"
                            error={errors.uploadCategory}
                        >
                            <NativeSelect
                                id="uploadCategory"
                                value={data.uploadCategory}
                                onChange={(e) =>
                                    setData("uploadCategory", e.target.value)
                                }
                                aria-invalid={!!errors.uploadCategory}
                            >
                                <option value="" disabled>
                                    Select category
                                </option>
                                <option value="contract">Contract</option>
                                <option value="invoice">Invoice</option>
                                <option value="quote">Quote</option>
                                <option value="compliance">Compliance</option>
                                <option value="correspondence">
                                    Correspondence
                                </option>
                                <option value="other">Other</option>
                            </NativeSelect>
                        </FormField>
                        <FormField
                            label="Description"
                            htmlFor="uploadDescription"
                            error={errors.uploadDescription}
                        >
                            <Textarea
                                id="uploadDescription"
                                rows={3}
                                value={data.uploadDescription}
                                onChange={(e) =>
                                    setData("uploadDescription", e.target.value)
                                }
                                placeholder="Optional description"
                            />
                        </FormField>
                        <FormField
                            label="File * (Max 10MB)"
                            htmlFor="uploadFile"
                            error={errors.uploadFile}
                        >
                            <Input
                                id="uploadFile"
                                type="file"
                                onChange={(e) =>
                                    setData(
                                        "uploadFile",
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                aria-invalid={!!errors.uploadFile}
                            />
                        </FormField>
                        {data.uploadFile && (
                            <div className="text-sm text-muted-foreground">
                                Selected: {data.uploadFile.name}
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={close}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Upload Document
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
