import { Head } from "@inertiajs/react";
import { ClientForm } from "@/components/clients/client-form";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import AppLayout from "@/layouts/app-layout";
import { create, index } from "@/routes/clients";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Clients", href: index() },
    { title: "Add Client", href: create() },
];

export default function ClientsCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add New Client" />
            <PageContainer>
                <PageHeader title="Add New Client" />
                <ClientForm />
            </PageContainer>
        </AppLayout>
    );
}
