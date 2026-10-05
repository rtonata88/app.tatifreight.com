import { Head } from "@inertiajs/react";
import { DocumentForm } from "@/components/documents/document-form";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import AppLayout from "@/layouts/app-layout";
import { index, upload } from "@/routes/documents";
import type { BreadcrumbItem, Option } from "@/types";

type Props = {
    clients: Option[];
    vehicles: Option[];
    bookings: Option[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Documents", href: index() },
    { title: "Upload document", href: upload() },
];

export default function DocumentsUpload({
    clients,
    vehicles,
    bookings,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload document" />
            <PageContainer>
                <PageHeader title="Upload document" />
                <DocumentForm
                    clients={clients}
                    vehicles={vehicles}
                    bookings={bookings}
                />
            </PageContainer>
        </AppLayout>
    );
}
