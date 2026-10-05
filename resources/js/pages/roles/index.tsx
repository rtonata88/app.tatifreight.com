import { Head } from '@inertiajs/react';
import { RoleBadge, ucfirst } from '@/components/admin/role-badge';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

type RoleSummary = {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
    permissions: string[];
};

type PermissionModule = { module: string; permissions: string[] };

type Props = {
    roles: RoleSummary[];
    modules: PermissionModule[];
};

/** ucfirst(str_replace('-', ' ', $module)) */
const moduleLabel = (module: string) => ucfirst(module.replace(/-/g, ' '));

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles & permissions', href: index() }];

export default function RolesIndex({ roles, modules }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & permissions" />
            <PageContainer>
                <PageHeader title="Roles & permissions" />

                {/* Roles overview */}
                <Card>
                    <CardHeader>
                        <CardTitle>System roles</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-5">
                            {roles.map((role) => (
                                <div key={role.id} className="rounded-lg border p-4 text-center md:p-6">
                                    <RoleBadge role={role.name} />
                                    <div className="mt-3 flex items-start justify-center gap-4 md:mt-4 md:block">
                                        <div>
                                            <p className="font-condensed text-2xl font-bold tabular-nums">{role.users_count}</p>
                                            <p className="text-sm text-muted-foreground">{role.users_count === 1 ? 'User' : 'Users'}</p>
                                        </div>
                                        <div className="md:mt-2">
                                            <p className="font-condensed text-2xl font-bold tabular-nums md:text-lg">{role.permissions_count}</p>
                                            <p className="text-sm text-muted-foreground md:text-xs">Permissions</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Permissions matrix */}
                <Card>
                    <CardHeader>
                        <CardTitle>Permissions matrix</CardTitle>
                        <CardDescription>Overview of permissions assigned to each role.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="relative">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="sticky left-0 z-10 bg-card text-xs uppercase">Module</TableHead>
                                        {roles.map((role) => (
                                            <TableHead key={role.id} className="text-center text-xs uppercase">
                                                {ucfirst(role.name)}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {modules.map(({ module, permissions }) => (
                                        <TableRow key={module}>
                                            <TableCell className="sticky left-0 z-10 bg-card font-medium">
                                                {moduleLabel(module)}
                                                <div className="text-xs font-normal text-muted-foreground">
                                                    {permissions.length} permission{permissions.length > 1 ? 's' : ''}
                                                </div>
                                            </TableCell>
                                            {roles.map((role) => (
                                                <TableCell key={role.id} className="text-center">
                                                    <AccessBadge granted={permissions.filter((p) => role.permissions.includes(p)).length} total={permissions.length} />
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {/* Phones: fade at the right edge hints that the matrix scrolls sideways. */}
                            <div aria-hidden className="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-card md:hidden" />
                        </div>
                    </CardContent>
                </Card>

                {/* Detailed permissions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Detailed permissions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {modules.map(({ module, permissions }) => (
                            <div key={module} className="rounded-lg border p-4">
                                <h3 className="mb-3 font-semibold">{moduleLabel(module)}</h3>
                                <div className="flex flex-wrap gap-2 md:grid md:grid-cols-2 md:gap-3 lg:grid-cols-4">
                                    {permissions.map((permission) => (
                                        <div key={permission} className="text-sm">
                                            <code className="rounded bg-muted px-2 py-1 font-mono text-xs">{permission}</code>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function AccessBadge({ granted, total }: { granted: number; total: number }) {
    if (granted === total) {
        return <StatusBadge tone="green">Full access</StatusBadge>;
    }
    if (granted === 0) {
        return <StatusBadge tone="red">No access</StatusBadge>;
    }

    return (
        <StatusBadge tone="yellow">
            Partial ({granted}/{total})
        </StatusBadge>
    );
}
