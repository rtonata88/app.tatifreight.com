import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import {
    LogbookForm,
    type LogbookFormOptions,
    type LogbookFormValues,
} from "@/components/logbook/logbook-form";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { edit, index } from "@/routes/logbook";
import type { BreadcrumbItem } from "@/types";

type Props = LogbookFormOptions & {
    logbook: { id: number } & Partial<
        Record<keyof LogbookFormValues, string | number | null>
    >;
};

export default function LogbookEdit({
    vehicles,
    drivers,
    bookings,
    logbook,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Logbook", href: index() },
        { title: "Edit entry", href: edit(logbook.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit logbook entry" />
            <PageContainer>
                <PageHeader
                    title="Edit logbook entry"
                    actions={
                        <Button asChild variant="ghost">
                            <Link href={index()}>
                                <ArrowLeft /> Back
                            </Link>
                        </Button>
                    }
                />
                <LogbookForm
                    vehicles={vehicles}
                    drivers={drivers}
                    bookings={bookings}
                    logbook={logbook}
                />
            </PageContainer>
        </AppLayout>
    );
}
