import { Head } from '@inertiajs/react';
import { RoleBadge, ucfirst } from '@/components/admin/role-badge';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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

const roleGradient: Record<string, string> = {
    admin: 'from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20',
    manager: 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
    accountant: 'from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
    dispatcher: 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
    driver: 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20',
};

/** ucfirst(str_replace('-', ' ', $module)) */
const moduleLabel = (module: string) => ucfirst(module.replace(/-/g, ' '));

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles & Permissions', href: index() }];

export default function RolesIndex({ roles, modules }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & Permissions" />
            <PageContainer>
                <PageHeader title="Roles & Permissions" />

                {/* Roles overview */}
                <Card>
                    <CardHeader>
                        <CardTitle>System Roles</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                            {roles.map((role) => (
                                <div
                                    key={role.id}
                                    className={cn('rounded-xl border bg-gradient-to-br p-6 text-center', roleGradient[role.name] ?? 'from-muted/40 to-muted')}
                                >
                                    <RoleBadge role={role.name} className="px-3 py-1 text-sm" />
                                    <div className="mt-4">
                                        <p className="text-2xl font-bold">{role.users_count}</p>
                                        <p className="text-sm text-muted-foreground">{role.users_count === 1 ? 'User' : 'Users'}</p>
                                    </div>
                                    <div className="mt-2">
                                        <p className="text-lg font-semibold">{role.permissions_count}</p>
                                        <p className="text-xs text-muted-foreground">Permissions</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Permissions matrix */}
                <Card>
                    <CardHeader>
                        <CardTitle>Permissions Matrix</CardTitle>
                        <CardDescription>Overview of permissions assigned to each role</CardDescription>
                    </CardHeader>
                    <CardContent>
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
                    </CardContent>
                </Card>

                {/* Detailed permissions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Detailed Permissions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {modules.map(({ module, permissions }) => (
                            <div key={module} className="rounded-lg border p-4">
                                <h3 className="mb-3 font-semibold">{moduleLabel(module)}</h3>
                                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                    {permissions.map((permission) => (
                                        <div key={permission} className="text-sm">
                                            <code className="rounded bg-muted px-2 py-1 text-xs">{permission}</code>
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
        return <StatusBadge tone="green">Full Access</StatusBadge>;
    }
    if (granted === 0) {
        return <StatusBadge tone="red">No Access</StatusBadge>;
    }

    return (
        <StatusBadge tone="yellow">
            Partial ({granted}/{total})
        </StatusBadge>
    );
}
