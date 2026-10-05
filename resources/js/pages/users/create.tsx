import { Head } from '@inertiajs/react';
import { UserForm, type RoleOption } from '@/components/admin/user-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index() },
    { title: 'Create', href: create() },
];

export default function UsersCreate({ roles }: { roles: RoleOption[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create New User" />
            <PageContainer>
                <PageHeader title="Create New User" />
                <UserForm roles={roles} />
            </PageContainer>
        </AppLayout>
    );
}
