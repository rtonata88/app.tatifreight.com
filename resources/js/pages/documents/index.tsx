import { Head, Link, router } from "@inertiajs/react";
import {
    Download,
    File,
    FileImage,
    FileText,
    Pencil,
    Plus,
    Search,
    Trash2,
} from "lucide-react";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { DataPagination } from "@/components/data-pagination";
import { EmptyState } from "@/components/empty-state";
import { PageContainer } from "@/components/page-container";
import { RowActionContent, rowActionProps } from "@/components/row-action";
import { PageHeader } from "@/components/page-header";
import { StatCard } from "@/components/stat-card";
import { StatusBadge } from "@/components/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { useFilters } from "@/hooks/use-filters";
import AppLayout from "@/layouts/app-layout";
import { formatDate } from "@/lib/format";
import { cn } from "@/lib/utils";
import { destroy, edit, file, index, upload } from "@/routes/documents";
import type { BreadcrumbItem, Paginated } from "@/types";

type DocumentRow = {
    id: number;
    title: string;
    file_name: string;
    file_type: string;
    description: string | null;
    category: string;
    related: { label: string; type: string } | null;
    uploaded_by: string | null;
    created_at: string | null;
    expiry_date: string | null;
    is_expired: boolean;
    is_expiring_soon: boolean;
    days_left: number | null;
    file_size_formatted: string;
};

type Can = { create: boolean; edit: boolean; delete: boolean };

type Props = {
    documents: Paginated<DocumentRow>;
    filters: { search: string; category: string; expiry: string };
    stats: {
        total: number;
        expired: number;
        expiring_soon: number;
        by_category: {
            contracts: number;
            licenses: number;
            insurance: number;
            receipts: number;
        };
    };
    can: Can;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: "Documents", href: index() }];

const ucfirst = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export default function DocumentsIndex({
    documents,
    filters: initialFilters,
    stats,
    can,
}: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);

    const deleteDocument = (document: DocumentRow, done: () => void) =>
        router.delete(destroy(document.id).url, {
            preserveScroll: true,
            onFinish: done,
        });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Document management" />
            <PageContainer>
                <PageHeader
                    title="Document management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={upload()}>
                                    <Plus /> Upload document
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-4">
                    <StatCard label="Total documents" value={stats.total} />
                    <StatCard
                        label="Expired"
                        value={stats.expired}
                        tone="negative"
                    />
                    <StatCard
                        label="Expiring soon"
                        value={stats.expiring_soon}
                        tone="warning"
                    />
                    <StatCard
                        label="Contracts"
                        value={stats.by_category.contracts}
                    />
                </div>

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) =>
                                        setFilter("search", e.target.value)
                                    }
                                    placeholder="Search documents..."
                                />
                            </div>
                            <NativeSelect
                                value={filters.category}
                                onChange={(e) =>
                                    setFilter("category", e.target.value)
                                }
                                aria-label="Filter by category"
                            >
                                <option value="">All categories</option>
                                <option value="contract">Contracts</option>
                                <option value="license">Licenses</option>
                                <option value="insurance">Insurance</option>
                                <option value="receipt">Receipts</option>
                                <option value="invoice">Invoices</option>
                                <option value="quote">Quotes</option>
                                <option value="other">Other</option>
                            </NativeSelect>
                            <NativeSelect
                                value={filters.expiry}
                                onChange={(e) =>
                                    setFilter("expiry", e.target.value)
                                }
                                aria-label="Filter by expiry"
                            >
                                <option value="">All documents</option>
                                <option value="expired">Expired</option>
                                <option value="expiring_soon">
                                    Expiring soon (30 days)
                                </option>
                            </NativeSelect>
                        </div>

                        {documents.data.length === 0 ? (
                            <EmptyState
                                icon={FileText}
                                title="No documents found."
                                description="Upload your first document to get started."
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
                                                <DocumentCell
                                                    document={document}
                                                />
                                                <StatusBadge tone="gray">
                                                    {ucfirst(document.category)}
                                                </StatusBadge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Related to
                                                    </div>
                                                    <Related
                                                        document={document}
                                                    />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Uploaded by
                                                    </div>
                                                    <UploadedBy
                                                        document={document}
                                                    />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Expiry date
                                                    </div>
                                                    <Expiry
                                                        document={document}
                                                    />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Size
                                                    </div>
                                                    <div className="text-sm">
                                                        {
                                                            document.file_size_formatted
                                                        }
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                <RowActions
                                                    document={document}
                                                    can={can}
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
                                                <TableHead>
                                                    Related to
                                                </TableHead>
                                                <TableHead>
                                                    Uploaded by
                                                </TableHead>
                                                <TableHead>
                                                    Expiry date
                                                </TableHead>
                                                <TableHead>Size</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {documents.data.map((document) => (
                                                <TableRow key={document.id}>
                                                    <TableCell className="max-w-56 whitespace-normal">
                                                        <DocumentCell
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone="gray">
                                                            {ucfirst(
                                                                document.category,
                                                            )}
                                                        </StatusBadge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Related
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <UploadedBy
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Expiry
                                                            document={document}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {
                                                            document.file_size_formatted
                                                        }
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <RowActions
                                                                document={
                                                                    document
                                                                }
                                                                can={can}
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
        </AppLayout>
    );
}

function DocumentCell({ document }: { document: DocumentRow }) {
    const Icon = document.file_type.includes("pdf")
        ? FileText
        : document.file_type.includes("image")
          ? FileImage
          : File;

    return (
        <div className="flex min-w-0 items-start gap-3">
            <Icon className="mt-1 size-8 shrink-0 text-muted-foreground" strokeWidth={1.6} />
            <div className="min-w-0 flex-1">
                <p className="line-clamp-2 text-sm font-medium md:truncate">{document.title}</p>
                <p className="truncate text-xs text-muted-foreground">
                    {document.file_name}
                </p>
                {document.description && (
                    <p className="mt-1 text-xs whitespace-normal text-muted-foreground/80">
                        {document.description}
                    </p>
                )}
            </div>
        </div>
    );
}

function Related({ document }: { document: DocumentRow }) {
    if (!document.related) {
        return <span className="text-sm text-muted-foreground">-</span>;
    }

    return (
        <div className="text-sm">
            <div className="font-medium">{document.related.label}</div>
            <div className="text-xs text-muted-foreground">
                {document.related.type}
            </div>
        </div>
    );
}

function UploadedBy({ document }: { document: DocumentRow }) {
    return (
        <div className="text-sm">
            <div>{document.uploaded_by}</div>
            <div className="text-xs text-muted-foreground">
                {formatDate(document.created_at)}
            </div>
        </div>
    );
}

function Expiry({ document }: { document: DocumentRow }) {
    if (!document.expiry_date) {
        return <span className="text-sm text-muted-foreground">-</span>;
    }

    return (
        <div className="text-sm">
            <div
                className={cn(
                    document.is_expired &&
                        "font-medium text-destructive",
                    !document.is_expired &&
                        document.is_expiring_soon &&
                        "text-warning",
                )}
            >
                {formatDate(document.expiry_date)}
            </div>
            {document.is_expired ? (
                <div className="text-xs text-destructive">
                    Expired
                </div>
            ) : (
                document.is_expiring_soon && (
                    <div className="text-xs text-warning">
                        {document.days_left} days left
                    </div>
                )
            )}
        </div>
    );
}

function RowActions({
    document,
    can,
    onDelete,
    stretch = false,
}: {
    document: DocumentRow;
    can: Can;
    onDelete: (document: DocumentRow, done: () => void) => void;
    stretch?: boolean;
}) {
    const className = stretch ? "flex-1" : undefined;
    const compact = !stretch;

    return (
        <>
            <Button asChild variant="ghost" {...rowActionProps(compact, "Download", className)}>
                <a href={file(document.id).url}>
                    <RowActionContent icon={Download} label="Download" compact={compact} />
                </a>
            </Button>
            {can.edit && (
                <Button asChild variant="ghost" {...rowActionProps(compact, "Edit", className)}>
                    <Link href={edit(document.id)}>
                        <RowActionContent icon={Pencil} label="Edit" compact={compact} />
                    </Link>
                </Button>
            )}
            {can.delete && (
                <ConfirmDialog
                    trigger={
                        <Button
                            variant={compact ? "ghost" : "destructive"}
                            {...rowActionProps(compact, "Delete", cn(className, compact && "text-destructive hover:text-destructive"))}
                        >
                            <RowActionContent icon={Trash2} label="Delete" compact={compact} />
                        </Button>
                    }
                    description="Are you sure you want to delete this document?"
                    onConfirm={(done) => onDelete(document, done)}
                />
            )}
        </>
    );
}
