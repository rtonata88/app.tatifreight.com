import { Head, Link, router } from "@inertiajs/react";
import {
    AlertTriangle,
    Clock,
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
import { PageHeader } from "@/components/page-header";
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
import type { LucideIcon } from "lucide-react";

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
            <Head title="Document Management" />
            <PageContainer>
                <PageHeader
                    title="Document Management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={upload()}>
                                    <Plus /> Upload Document
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <Stat
                        label="Total Documents"
                        value={stats.total}
                        icon={FileText}
                        tone="blue"
                    />
                    <Stat
                        label="Expired"
                        value={stats.expired}
                        icon={AlertTriangle}
                        tone="red"
                    />
                    <Stat
                        label="Expiring Soon"
                        value={stats.expiring_soon}
                        icon={Clock}
                        tone="yellow"
                    />
                    <Stat
                        label="Contracts"
                        value={stats.by_category.contracts}
                        icon={FileText}
                        tone="green"
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
                                <option value="">All Categories</option>
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
                                <option value="">All Documents</option>
                                <option value="expired">Expired</option>
                                <option value="expiring_soon">
                                    Expiring Soon (30 days)
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
                                                        Related To
                                                    </div>
                                                    <Related
                                                        document={document}
                                                    />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Uploaded By
                                                    </div>
                                                    <UploadedBy
                                                        document={document}
                                                    />
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Expiry Date
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
                                                    Related To
                                                </TableHead>
                                                <TableHead>
                                                    Uploaded By
                                                </TableHead>
                                                <TableHead>
                                                    Expiry Date
                                                </TableHead>
                                                <TableHead>Size</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {documents.data.map((document) => (
                                                <TableRow key={document.id}>
                                                    <TableCell className="max-w-xs">
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

const statTones = {
    blue: {
        card: "bg-blue-50 dark:bg-blue-500/10",
        value: "text-blue-700 dark:text-blue-300",
        icon: "text-blue-500",
    },
    red: {
        card: "bg-red-50 dark:bg-red-500/10",
        value: "text-red-700 dark:text-red-300",
        icon: "text-red-500",
    },
    yellow: {
        card: "bg-yellow-50 dark:bg-yellow-500/10",
        value: "text-yellow-700 dark:text-yellow-300",
        icon: "text-yellow-500",
    },
    green: {
        card: "bg-green-50 dark:bg-green-500/10",
        value: "text-green-700 dark:text-green-300",
        icon: "text-green-500",
    },
};

function Stat({
    label,
    value,
    icon: Icon,
    tone,
}: {
    label: string;
    value: number;
    icon: LucideIcon;
    tone: keyof typeof statTones;
}) {
    const t = statTones[tone];
    return (
        <Card className={cn("gap-0 py-0", t.card)}>
            <CardContent className="flex items-center justify-between p-4">
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className={cn("text-2xl font-bold", t.value)}>{value}</p>
                </div>
                <Icon className={cn("size-8", t.icon)} />
            </CardContent>
        </Card>
    );
}

function DocumentCell({ document }: { document: DocumentRow }) {
    const Icon = document.file_type.includes("pdf")
        ? FileText
        : document.file_type.includes("image")
          ? FileImage
          : File;
    const color = document.file_type.includes("pdf")
        ? "text-red-500"
        : document.file_type.includes("image")
          ? "text-green-500"
          : "text-muted-foreground";

    return (
        <div className="flex min-w-0 items-start gap-3">
            <Icon className={cn("mt-1 size-8 shrink-0", color)} />
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{document.title}</p>
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
                        "font-medium text-red-600 dark:text-red-400",
                    !document.is_expired &&
                        document.is_expiring_soon &&
                        "text-yellow-600 dark:text-yellow-400",
                )}
            >
                {formatDate(document.expiry_date)}
            </div>
            {document.is_expired ? (
                <div className="text-xs text-red-600 dark:text-red-400">
                    Expired
                </div>
            ) : (
                document.is_expiring_soon && (
                    <div className="text-xs text-yellow-600 dark:text-yellow-400">
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

    return (
        <>
            <Button asChild size="sm" variant="ghost" className={className}>
                <a href={file(document.id).url}>
                    <Download /> Download
                </a>
            </Button>
            {can.edit && (
                <Button asChild size="sm" variant="ghost" className={className}>
                    <Link href={edit(document.id)}>
                        <Pencil /> Edit
                    </Link>
                </Button>
            )}
            {can.delete && (
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
