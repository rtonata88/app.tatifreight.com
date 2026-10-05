import { Head } from '@inertiajs/react';
import { UserForm, type RoleOption } from '@/components/admin/user-form';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type Props = {
    roles: RoleOption[];
    user: { id: number; name: string; email: string; role: string };
};

export default function UsersEdit({ roles, user }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: index() },
        { title: user.name, href: edit(user.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit user: ${user.name}`} />
            <PageContainer>
                <PageHeader title={`Edit user: ${user.name}`} />
                <UserForm roles={roles} user={user} />
            </PageContainer>
        </AppLayout>
    );
}
