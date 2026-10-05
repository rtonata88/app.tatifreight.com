import { Head, Link, router } from "@inertiajs/react";
import {
    Ellipsis,
    FileText,
    FolderOpen,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from "lucide-react";
import { useState } from "react";
import { DataPagination } from "@/components/data-pagination";
import { EmptyState } from "@/components/empty-state";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatusBadge } from "@/components/status-badge";
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
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
import { formatMoney } from "@/lib/format";
import {
    create,
    destroy,
    documents,
    edit,
    index,
    statement,
} from "@/routes/clients";
import type { BreadcrumbItem, Paginated } from "@/types";

type ClientRow = {
    id: number;
    name: string;
    company_name: string | null;
    email: string;
    phone: string | null;
    classification: string;
    credit_limit: number;
    is_active: boolean;
};

type Can = {
    create: boolean;
    edit: boolean;
    delete: boolean;
    viewDocuments: boolean;
};

type Props = {
    clients: Paginated<ClientRow>;
    filters: { search: string; classification: string; status: string };
    can: Can;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: "Clients", href: index() }];

const ucfirst = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export default function ClientsIndex({
    clients,
    filters: initialFilters,
    can,
}: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    const [deleting, setDeleting] = useState<ClientRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleting) return;
        setProcessing(true);
        router.delete(destroy(deleting.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Client management" />
            <PageContainer>
                <PageHeader
                    title="Client management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Add client
                                </Link>
                            </Button>
                        )
                    }
                />

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
                                    placeholder="Search clients..."
                                />
                            </div>
                            <NativeSelect
                                value={filters.classification}
                                onChange={(e) =>
                                    setFilter("classification", e.target.value)
                                }
                                aria-label="Filter by type"
                            >
                                <option value="">All types</option>
                                <option value="adhoc">Ad-hoc</option>
                                <option value="contract">Contract</option>
                            </NativeSelect>
                            <NativeSelect
                                value={filters.status}
                                onChange={(e) =>
                                    setFilter("status", e.target.value)
                                }
                                aria-label="Filter by status"
                            >
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </NativeSelect>
                        </div>

                        {clients.data.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title="No clients found."
                                description="Add your first client to get started."
                            />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {clients.data.map((client) => (
                                        <div
                                            key={client.id}
                                            className="space-y-3 rounded-lg border p-4"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    {can.edit ? (
                                                        <Link
                                                            href={edit(
                                                                client.id,
                                                            )}
                                                            className="font-semibold underline-offset-4 hover:underline"
                                                        >
                                                            {client.name}
                                                        </Link>
                                                    ) : (
                                                        <div className="font-semibold">
                                                            {client.name}
                                                        </div>
                                                    )}
                                                    {client.company_name && (
                                                        <div className="text-sm text-muted-foreground">
                                                            {
                                                                client.company_name
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            {(client.email || client.phone) && (
                                                <div className="border-t pt-3 text-sm">
                                                    {client.email && (
                                                        <a
                                                            href={`mailto:${client.email}`}
                                                            className="block break-all hover:text-primary"
                                                        >
                                                            {client.email}
                                                        </a>
                                                    )}
                                                    {client.phone && (
                                                        <a
                                                            href={`tel:${client.phone}`}
                                                            className="block text-muted-foreground hover:text-primary"
                                                        >
                                                            {client.phone}
                                                        </a>
                                                    )}
                                                </div>
                                            )}
                                            <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-3">
                                                <div className="flex gap-2">
                                                    <ClassificationBadge
                                                        value={
                                                            client.classification
                                                        }
                                                    />
                                                    <ActiveBadge
                                                        active={
                                                            client.is_active
                                                        }
                                                    />
                                                </div>
                                                <div className="text-sm">
                                                    <span className="text-muted-foreground">
                                                        Credit limit:{" "}
                                                    </span>
                                                    <span className="font-mono font-medium tabular-nums">
                                                        {formatMoney(
                                                            client.credit_limit,
                                                            "N$",
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="border-t pt-3">
                                                <RowActions
                                                    client={client}
                                                    can={can}
                                                    onDelete={setDeleting}
                                                    triggerClassName="w-full"
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
                                                <TableHead>
                                                    Client name
                                                </TableHead>
                                                <TableHead>Company</TableHead>
                                                <TableHead>Contact</TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead className="text-right">
                                                    Credit limit
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {clients.data.map((client) => (
                                                <TableRow key={client.id}>
                                                    <TableCell>
                                                        <div className="font-semibold">
                                                            {client.name}
                                                        </div>
                                                        {client.company_name && (
                                                            <div className="text-sm text-muted-foreground">
                                                                {
                                                                    client.company_name
                                                                }
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {client.company_name ||
                                                            "—"}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div>
                                                                {client.email}
                                                            </div>
                                                            {client.phone && (
                                                                <div className="text-muted-foreground">
                                                                    {
                                                                        client.phone
                                                                    }
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <ClassificationBadge
                                                            value={
                                                                client.classification
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono tabular-nums">
                                                        {formatMoney(
                                                            client.credit_limit,
                                                            "N$",
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <ActiveBadge
                                                            active={
                                                                client.is_active
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <RowActions
                                                            client={client}
                                                            can={can}
                                                            onDelete={
                                                                setDeleting
                                                            }
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={clients} />
                    </CardContent>
                </Card>
            </PageContainer>

            <AlertDialog
                open={deleting !== null}
                onOpenChange={(open) =>
                    !open && !processing && setDeleting(null)
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this client?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>
                            Cancel
                        </AlertDialogCancel>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={confirmDelete}
                        >
                            Delete
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}

function ClassificationBadge({ value }: { value: string }) {
    return (
        <StatusBadge tone={value === "contract" ? "blue" : "gray"}>
            {ucfirst(value)}
        </StatusBadge>
    );
}

function ActiveBadge({ active }: { active: boolean }) {
    return (
        <StatusBadge tone={active ? "green" : "red"}>
            {active ? "Active" : "Inactive"}
        </StatusBadge>
    );
}

function RowActions({
    client,
    can,
    onDelete,
    triggerClassName,
}: {
    client: ClientRow;
    can: Can;
    onDelete: (client: ClientRow) => void;
    triggerClassName?: string;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button size="sm" variant="ghost" className={triggerClassName}>
                    <Ellipsis /> Actions
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {can.edit && (
                    <DropdownMenuItem asChild>
                        <Link href={edit(client.id)}>
                            <Pencil /> Edit client
                        </Link>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem asChild>
                    <Link href={statement(client.id)}>
                        <FileText /> Customer statement
                    </Link>
                </DropdownMenuItem>
                {can.viewDocuments && (
                    <DropdownMenuItem asChild>
                        <Link href={documents(client.id)}>
                            <FolderOpen /> Documents
                        </Link>
                    </DropdownMenuItem>
                )}
                {can.delete && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => onDelete(client)}
                        >
                            <Trash2 /> Delete client
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
