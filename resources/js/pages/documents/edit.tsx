import { Head } from "@inertiajs/react";
import { ExternalLink, FileIcon } from "lucide-react";
import {
    DocumentForm,
    type EditableDocument,
} from "@/components/documents/document-form";
import { FormSection } from "@/components/form-section";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatusBadge } from "@/components/status-badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import AppLayout from "@/layouts/app-layout";
import { formatDateTime } from "@/lib/format";
import { edit, index } from "@/routes/documents";
import type { BreadcrumbItem, Option } from "@/types";

type Version = {
    id: number;
    version: number;
    file_name: string;
    created_at: string | null;
    uploaded_by: string | null;
    file_size_formatted: string;
    file_url: string;
};

type Props = {
    clients: Option[];
    vehicles: Option[];
    bookings: Option[];
    document: EditableDocument & {
        version: number;
        is_expired: boolean;
        is_expiring_soon: boolean;
        file_name: string;
        file_type: string;
        file_size_formatted: string;
        file_url: string;
        uploaded_by: string | null;
        created_at: string | null;
    };
    versions: Version[];
};

export default function DocumentsEdit({
    clients,
    vehicles,
    bookings,
    document,
    versions,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Documents", href: index() },
        { title: document.title, href: edit(document.id) },
    ];

    const history =
        versions.length > 0 ? (
            <FormSection title="Version History" columns={1}>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Version</TableHead>
                            <TableHead>File Name</TableHead>
                            <TableHead>Uploaded</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {versions.map((version) => (
                            <TableRow key={version.id}>
                                <TableCell>v{version.version}</TableCell>
                                <TableCell>{version.file_name}</TableCell>
                                <TableCell>
                                    {formatDateTime(version.created_at).replace(
                                        / (\d{2}:\d{2})$/,
                                        ", $1",
                                    )}
                                    <div className="text-xs text-muted-foreground">
                                        by {version.uploaded_by}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {version.file_size_formatted}
                                </TableCell>
                                <TableCell>
                                    <a
                                        href={version.file_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-sm text-primary underline-offset-4 hover:underline"
                                    >
                                        Download
                                    </a>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </FormSection>
        ) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Document" />
            <PageContainer>
                <PageHeader
                    title="Edit Document"
                    actions={
                        <>
                            {document.is_expired ? (
                                <StatusBadge tone="red">Expired</StatusBadge>
                            ) : (
                                document.is_expiring_soon && (
                                    <StatusBadge tone="yellow">
                                        Expiring Soon
                                    </StatusBadge>
                                )
                            )}
                            {document.version > 1 && (
                                <StatusBadge tone="blue">
                                    v{document.version}
                                </StatusBadge>
                            )}
                        </>
                    }
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Current File</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-start gap-4 rounded border bg-muted/50 p-4">
                            <FileIcon className="size-12 shrink-0 text-blue-500" />
                            <div className="min-w-0 flex-1">
                                <p className="font-medium break-all">
                                    {document.file_name}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {document.file_size_formatted} •{" "}
                                    {document.file_type}
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Uploaded by {document.uploaded_by} on{" "}
                                    {formatDateTime(
                                        document.created_at,
                                    ).replace(/ (\d{2}:\d{2})$/, ", $1")}
                                </p>
                            </div>
                            <a
                                href={document.file_url}
                                target="_blank"
                                rel="noreferrer"
                                className="text-primary hover:opacity-80"
                                aria-label="Open file"
                            >
                                <ExternalLink className="size-5" />
                            </a>
                        </div>
                    </CardContent>
                </Card>

                <DocumentForm
                    clients={clients}
                    vehicles={vehicles}
                    bookings={bookings}
                    document={document}
                    beforeNotes={history}
                />
            </PageContainer>
        </AppLayout>
    );
}
