import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import {
    LogbookForm,
    type LogbookFormOptions,
} from "@/components/logbook/logbook-form";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { create, index } from "@/routes/logbook";
import type { BreadcrumbItem } from "@/types";

type Props = LogbookFormOptions & {
    defaults: { date: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Logbook", href: index() },
    { title: "New Entry", href: create() },
];

export default function LogbookCreate({
    vehicles,
    drivers,
    bookings,
    defaults,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Logbook Entry" />
            <PageContainer>
                <PageHeader
                    title="New Logbook Entry"
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
                    defaults={defaults}
                />
            </PageContainer>
        </AppLayout>
    );
}
