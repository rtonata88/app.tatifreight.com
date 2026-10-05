import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2, Users } from 'lucide-react';
import { RoleBadge } from '@/components/admin/role-badge';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataPagination } from '@/components/data-pagination';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/users';
import type { BreadcrumbItem, Paginated } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string | null;
    is_self: boolean;
};

type Props = {
    users: Paginated<UserRow>;
    filters: { search: string; role: string };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index() }];

export default function UsersIndex({ users, filters: initialFilters }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);

    const deleteUser = (user: UserRow, done: () => void) => router.delete(destroy(user.id).url, { preserveScroll: true, onFinish: done });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />
            <PageContainer>
                <PageHeader
                    title="User Management"
                    actions={
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> Add User
                            </Link>
                        </Button>
                    }
                />

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) => setFilter('search', e.target.value)}
                                    placeholder="Search by name or email..."
                                />
                            </div>
                            <NativeSelect value={filters.role} onChange={(e) => setFilter('role', e.target.value)} aria-label="Filter by role">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="dispatcher">Dispatcher</option>
                                <option value="accountant">Accountant</option>
                                <option value="driver">Driver</option>
                            </NativeSelect>
                        </div>

                        {users.data.length === 0 ? (
                            <EmptyState icon={Users} title="No users found." />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {users.data.map((user) => (
                                        <div key={user.id} className="space-y-3 rounded-lg border p-4">
                                            <div>
                                                <div className="font-semibold">{user.name}</div>
                                                <div className="text-sm break-all text-muted-foreground">{user.email}</div>
                                            </div>
                                            <div className="flex items-center justify-between gap-2 border-t pt-3">
                                                <Roles roles={user.roles} />
                                                <span className="text-sm text-muted-foreground">{formatDate(user.created_at)}</span>
                                            </div>
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                <RowActions user={user} onDelete={deleteUser} stretch />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Name</TableHead>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Role(s)</TableHead>
                                                <TableHead>Created</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {users.data.map((user) => (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-semibold">{user.name}</TableCell>
                                                    <TableCell>{user.email}</TableCell>
                                                    <TableCell>
                                                        <Roles roles={user.roles} />
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{formatDate(user.created_at)}</TableCell>
                                                    <TableCell>
                                                        <div className="flex justify-end gap-1">
                                                            <RowActions user={user} onDelete={deleteUser} />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={users} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function Roles({ roles }: { roles: string[] }) {
    if (roles.length === 0) {
        return <span className="text-sm text-muted-foreground">No role</span>;
    }

    return (
        <div className="flex flex-wrap gap-1">
            {roles.map((role) => (
                <RoleBadge key={role} role={role} />
            ))}
        </div>
    );
}

function RowActions({ user, onDelete, stretch = false }: { user: UserRow; onDelete: (user: UserRow, done: () => void) => void; stretch?: boolean }) {
    const className = stretch ? 'flex-1' : undefined;

    return (
        <>
            <Button asChild size="sm" variant="ghost" className={className}>
                <Link href={edit(user.id)}>
                    <Pencil /> Edit
                </Link>
            </Button>
            {!user.is_self && (
                <ConfirmDialog
                    trigger={
                        <Button size="sm" variant="destructive" className={className}>
                            <Trash2 /> Delete
                        </Button>
                    }
                    description="Are you sure you want to delete this user?"
                    onConfirm={(done) => onDelete(user, done)}
                />
            )}
        </>
    );
}
