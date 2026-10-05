import { Head } from "@inertiajs/react";
import { Download, ExternalLink, FileIcon } from "lucide-react";
import {
    DocumentForm,
    type EditableDocument,
} from "@/components/documents/document-form";
import { FormSection } from "@/components/form-section";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatusBadge } from "@/components/status-badge";
import { Button } from "@/components/ui/button";
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

    const uploadedAt = (value: string | null) =>
        formatDateTime(value).replace(/ (\d{2}:\d{2})$/, ", $1");

    const history =
        versions.length > 0 ? (
            <FormSection title="Version history" columns={1}>
                {/* Phones: cards */}
                <div className="space-y-3 md:hidden">
                    {versions.map((version) => (
                        <div
                            key={version.id}
                            className="space-y-3 rounded-lg border bg-card p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <p className="min-w-0 text-sm font-medium break-all">
                                    {version.file_name}
                                </p>
                                <span className="font-mono text-sm">
                                    v{version.version}
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <div>
                                    <div className="text-xs text-muted-foreground">
                                        Uploaded
                                    </div>
                                    {uploadedAt(version.created_at)}
                                    <div className="text-xs text-muted-foreground">
                                        by {version.uploaded_by}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-xs text-muted-foreground">
                                        Size
                                    </div>
                                    {version.file_size_formatted}
                                </div>
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="w-full"
                            >
                                <a
                                    href={version.file_url}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Download /> Download
                                </a>
                            </Button>
                        </div>
                    ))}
                </div>

                {/* Tablets and up: table */}
                <div className="hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Version</TableHead>
                                <TableHead>File name</TableHead>
                                <TableHead>Uploaded</TableHead>
                                <TableHead>Size</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {versions.map((version) => (
                                <TableRow key={version.id}>
                                    <TableCell className="font-mono">v{version.version}</TableCell>
                                    <TableCell>{version.file_name}</TableCell>
                                    <TableCell>
                                        {uploadedAt(version.created_at)}
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
                </div>
            </FormSection>
        ) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit document" />
            <PageContainer>
                <PageHeader
                    title="Edit document"
                    actions={
                        <>
                            {document.is_expired ? (
                                <StatusBadge tone="red">Expired</StatusBadge>
                            ) : (
                                document.is_expiring_soon && (
                                    <StatusBadge tone="yellow">
                                        Expiring soon
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
                        <CardTitle>Current file</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-start gap-4 rounded border bg-muted p-4">
                            <FileIcon className="size-12 shrink-0 text-muted-foreground" strokeWidth={1.6} />
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
