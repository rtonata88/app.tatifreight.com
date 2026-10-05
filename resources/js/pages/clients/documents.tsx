import { Head, Link, router } from "@inertiajs/react";
import { ArrowLeft, Download, FolderOpen, Trash2, Upload } from "lucide-react";
import { useState } from "react";
import { UploadDocumentDialog } from "@/components/clients/upload-document-dialog";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { DataPagination } from "@/components/data-pagination";
import { EmptyState } from "@/components/empty-state";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatusBadge, type BadgeTone } from "@/components/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import AppLayout from "@/layouts/app-layout";
import { formatDate, formatNumber } from "@/lib/format";
import { documents as clientDocuments, index } from "@/routes/clients";
import { destroy } from "@/routes/clients/documents";
import { download } from "@/routes/documents";
import type { BreadcrumbItem, Paginated } from "@/types";

type DocumentRow = {
    id: number;
    title: string;
    description: string | null;
    category: string;
    file_name: string;
    file_type: string;
    file_size_kb: number;
    created_at: string | null;
    uploader: string | null;
};

type Props = {
    client: {
        id: number;
        name: string;
        company_name: string | null;
        email: string;
        phone: string | null;
        is_active: boolean;
    };
    documents: Paginated<DocumentRow>;
    can: { create: boolean; delete: boolean };
};

const categoryTone: Record<string, BadgeTone> = {
    contract: "blue",
    invoice: "green",
    quote: "yellow",
    compliance: "purple",
};

const ucfirst = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export default function ClientDocuments({ client, documents, can }: Props) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const displayName = client.company_name || client.name;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Clients", href: index() },
        { title: "Documents", href: clientDocuments(client.id) },
    ];

    const deleteDocument = (document: DocumentRow, done: () => void) =>
        router.delete(
            destroy({ client: client.id, document: document.id }).url,
            { preserveScroll: true, onFinish: done },
        );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Client Documents - ${displayName}`} />
            <PageContainer>
                <PageHeader
                    title="Client Documents"
                    description={displayName}
                    actions={
                        <>
                            <Button asChild variant="ghost">
                                <Link href={index()}>
                                    <ArrowLeft /> Back to Clients
                                </Link>
                            </Button>
                            {can.create && (
                                <Button onClick={() => setUploadOpen(true)}>
                                    <Upload /> Upload Document
                                </Button>
                            )}
                        </>
                    }
                />

                <Card className="bg-blue-50 dark:bg-blue-500/10">
                    <CardContent className="flex items-center gap-4">
                        <div className="min-w-0 flex-1">
                            <div className="font-semibold">{displayName}</div>
                            <div className="text-sm break-all text-muted-foreground">
                                {client.email}
                                {client.phone && ` • ${client.phone}`}
                            </div>
                        </div>
                        <StatusBadge tone={client.is_active ? "green" : "red"}>
                            {client.is_active ? "Active" : "Inactive"}
                        </StatusBadge>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="space-y-4">
                        {documents.data.length === 0 ? (
                            <EmptyState
                                icon={FolderOpen}
                                title="No documents uploaded yet."
                                description='Click "Upload Document" to add documents for this client.'
                            />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {documents.data.map((document) => (
                                        <div
                                            key={document.id}
                                            className="space-y-3 rounded-lg border p-4"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <div className="font-medium">
                                                        {document.title}
                                                    </div>
                                                    {document.description && (
                                                        <div className="text-sm text-muted-foreground">
                                                            {
                                                                document.description
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                                <CategoryBadge
                                                    category={document.category}
                                                />
                                            </div>
                                            <div className="border-t pt-3">
                                                <FileInfo document={document} />
                                            </div>
                                            <div className="border-t pt-3">
                                                <Uploaded document={document} />
                                            </div>
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                <RowActions
                                                    document={document}
                                                    canDelete={can.delete}
                                                    onDelete={deleteDocument}
                                                    stretch
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Document</TableHead>
                                                <TableHead>Category</TableHead>
                                                <TableHead>File Info</TableHead>
                                                <TableHead>Uploaded</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {documents.data.map((document) => (
                                                <TableRow key={document.id}>
                                                    <TableCell className="whitespace-normal">
                                                        <div className="font-medium">
                                                            {document.title}
                                                        </div>
                                                        {document.description && (
                                                            <div className="text-sm text-muted-foreground">
                                                                {
                                                                    document.description
                                                                }
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <CategoryBadge
                                                            category={
                                                                document.category
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <FileInfo
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Uploaded
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <RowActions
                                                                document={
                                                                    document
                                                                }
                                                                canDelete={
                                                                    can.delete
                                                                }
                                                                onDelete={
                                                                    deleteDocument
                                                                }
                                                            />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={documents} />
                    </CardContent>
                </Card>
            </PageContainer>

            {can.create && (
                <UploadDocumentDialog
                    clientId={client.id}
                    open={uploadOpen}
                    onOpenChange={setUploadOpen}
                />
            )}
        </AppLayout>
    );
}

function CategoryBadge({ category }: { category: string }) {
    return (
        <StatusBadge tone={categoryTone[category] ?? "gray"}>
            {ucfirst(category)}
        </StatusBadge>
    );
}

function FileInfo({ document }: { document: DocumentRow }) {
    return (
        <div className="text-sm">
            <div className="font-medium break-all">{document.file_name}</div>
            <div className="text-muted-foreground">
                {document.file_type.toUpperCase()} •{" "}
                {formatNumber(document.file_size_kb, 1)} KB
            </div>
        </div>
    );
}

function Uploaded({ document }: { document: DocumentRow }) {
    return (
        <div className="text-sm">
            <div>{formatDate(document.created_at)}</div>
            <div className="text-muted-foreground">
                by {document.uploader ?? "Unknown"}
            </div>
        </div>
    );
}

function RowActions({
    document,
    canDelete,
    onDelete,
    stretch = false,
}: {
    document: DocumentRow;
    canDelete: boolean;
    onDelete: (document: DocumentRow, done: () => void) => void;
    stretch?: boolean;
}) {
    const className = stretch ? "flex-1" : undefined;

    return (
        <>
            <Button asChild size="sm" variant="ghost" className={className}>
                <a href={download(document.id).url}>
                    <Download /> Download
                </a>
            </Button>
            {canDelete && (
                <ConfirmDialog
                    trigger={
                        <Button
                            size="sm"
                            variant="destructive"
                            className={className}
                        >
                            <Trash2 /> Delete
                        </Button>
                    }
                    description="Are you sure you want to delete this document?"
                    onConfirm={(done) => onDelete(document, done)}
                />
            )}
        </>
    );
}
