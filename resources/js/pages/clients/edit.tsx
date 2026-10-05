import { Head } from "@inertiajs/react";
import {
    ClientForm,
    type EditableClient,
} from "@/components/clients/client-form";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import AppLayout from "@/layouts/app-layout";
import { edit, index } from "@/routes/clients";
import type { BreadcrumbItem } from "@/types";

type Props = {
    client: EditableClient & { name: string };
};

export default function ClientsEdit({ client }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Clients", href: index() },
        { title: client.name, href: edit(client.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Client: ${client.name}`} />
            <PageContainer>
                <PageHeader title={`Edit Client: ${client.name}`} />
                <ClientForm client={client} />
            </PageContainer>
        </AppLayout>
    );
}
