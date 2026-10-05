import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Pencil } from "lucide-react";
import type { ReactNode } from "react";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatCard } from "@/components/stat-card";
import { StatusBadge, type BadgeTone } from "@/components/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { VehicleRecentList } from "@/components/vehicles/vehicle-recent-list";
import AppLayout from "@/layouts/app-layout";
import { formatDate, formatMoney, formatNumber, humanize } from "@/lib/format";
import { index as bookingsIndex } from "@/routes/bookings";
import { index as logbookIndex } from "@/routes/logbook";
import { index as mdcIndex } from "@/routes/mdc";
import { edit, index, show } from "@/routes/vehicles";
import type { BreadcrumbItem } from "@/types";

type Expiry = { date: string; past: boolean; soon: boolean } | null;

type VehicleDetail = {
    id: number;
    reg_number: string;
    status: string;
    make: string | null;
    model: string | null;
    year: number | null;
    type: string | null;
    vin: string | null;
    current_mileage: number;
    gps_device_id: string | null;
    gvm_tonnes: number | null;
    load_capacity: number | null;
    tare_weight: number | null;
    mdc_rate_card: {
        category_name: string;
        rate_per_100km: number;
        suggested: boolean;
    } | null;
    insurance_expiry: Expiry;
    disc_expiry: Expiry;
    roadworthy_expiry: Expiry;
    next_service_date: Expiry;
    next_service_mileage: number | null;
    notes: string | null;
    photo_url: string | null;
};

type Props = {
    vehicle: VehicleDetail;
    bookings: {
        id: number;
        booking_number: string;
        client: string | null;
        start_date: string | null;
        status: string;
    }[];
    logbooks: {
        id: number;
        origin_from: string;
        origin_to: string;
        driver: string | null;
        date: string | null;
        distance: number;
    }[];
    mdcCalculations: {
        id: number;
        mdc_amount: number;
        distance_km: number;
        calculation_date: string | null;
        payment_status: string;
    }[];
    inspections: {
        id: number;
        inspection_type: string;
        inspector: string | null;
        inspection_date: string | null;
        passed: boolean;
    }[];
    stats: {
        total_bookings: number;
        total_mdc_charges: number;
        total_mdc_paid: number;
        total_mdc_outstanding: number;
        total_expenses: number;
        total_distance: number;
    };
    can: { edit: boolean };
};

const statusTone: Record<string, BadgeTone> = {
    available: "green",
    in_use: "blue",
    maintenance: "yellow",
    retired: "red",
};

const bookingTone: Record<string, BadgeTone> = {
    pending: "yellow",
    confirmed: "blue",
    in_progress: "green",
    completed: "gray",
    cancelled: "red",
};

const paymentTone: Record<string, BadgeTone> = {
    paid: "green",
    partially_paid: "yellow",
    unpaid: "red",
};

/** Same as PHP ucfirst(): only the first letter changes. */
const ucfirst = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export default function VehicleShow({
    vehicle,
    bookings,
    logbooks,
    mdcCalculations,
    inspections,
    stats,
    can,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Vehicles", href: index() },
        { title: vehicle.reg_number, href: show(vehicle.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Vehicle ${vehicle.reg_number}`} />
            <PageContainer>
                <PageHeader
                    title="Vehicle details"
                    actions={
                        <>
                            <Button asChild variant="ghost">
                                <Link href={index()}>
                                    <ArrowLeft /> Back to fleet
                                </Link>
                            </Button>
                            {can.edit && (
                                <Button asChild>
                                    <Link href={edit(vehicle.id)}>
                                        <Pencil /> Edit vehicle
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {/* Vehicle header */}
                <Card>
                    <CardContent className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-4">
                                <h2 className="font-mono text-2xl font-bold">
                                    {vehicle.reg_number}
                                </h2>
                                <StatusBadge
                                    tone={statusTone[vehicle.status] ?? "gray"}
                                >
                                    {humanize(vehicle.status)}
                                </StatusBadge>
                            </div>
                            <p className="mt-2 text-lg text-muted-foreground">
                                {vehicle.make} {vehicle.model}
                                {vehicle.year && (
                                    <span className="text-muted-foreground/80">
                                        {" "}
                                        ({vehicle.year})
                                    </span>
                                )}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {vehicle.type}
                            </p>
                        </div>
                        {vehicle.photo_url && (
                            <div className="size-32 shrink-0 overflow-hidden rounded-lg border">
                                <img
                                    src={vehicle.photo_url}
                                    alt={vehicle.reg_number}
                                    className="size-full object-cover"
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Statistics */}
                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total bookings"
                        value={stats.total_bookings}
                    />
                    <StatCard
                        label="MDC outstanding"
                        value={formatMoney(stats.total_mdc_outstanding, "N$")}
                        tone="warning"
                    />
                    <StatCard
                        label="Total expenses"
                        value={formatMoney(stats.total_expenses, "N$")}
                    />
                    <StatCard
                        label="Total distance"
                        value={`${formatNumber(stats.total_distance)} km`}
                    />
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Left column: vehicle details */}
                    <div className="space-y-6 lg:col-span-2">
                        <DetailCard title="Basic information">
                            <Detail label="Registration number">
                                <span className="font-mono">{vehicle.reg_number}</span>
                            </Detail>
                            <Detail label="Vehicle type">{vehicle.type}</Detail>
                            {vehicle.vin && (
                                <Detail label="VIN"><span className="font-mono">{vehicle.vin}</span></Detail>
                            )}
                            {vehicle.make && (
                                <Detail label="Make">{vehicle.make}</Detail>
                            )}
                            {vehicle.model && (
                                <Detail label="Model">{vehicle.model}</Detail>
                            )}
                            {vehicle.year && (
                                <Detail label="Year">{vehicle.year}</Detail>
                            )}
                            <Detail label="Current mileage">
                                {formatNumber(vehicle.current_mileage)} km
                            </Detail>
                            {vehicle.gps_device_id && (
                                <Detail label="GPS Device ID">
                                    {vehicle.gps_device_id}
                                </Detail>
                            )}
                        </DetailCard>

                        <DetailCard title="Specifications">
                            {!!vehicle.gvm_tonnes && (
                                <Detail label="GVM (Gross Vehicle Mass)">
                                    {formatNumber(vehicle.gvm_tonnes, 2)} tonnes
                                </Detail>
                            )}
                            {!!vehicle.load_capacity && (
                                <Detail label="Load capacity">
                                    {formatNumber(vehicle.load_capacity, 2)}{" "}
                                    tonnes
                                </Detail>
                            )}
                            {!!vehicle.tare_weight && (
                                <Detail label="Tare weight">
                                    {formatNumber(vehicle.tare_weight, 2)} kg
                                </Detail>
                            )}
                            {vehicle.mdc_rate_card && (
                                <Detail
                                    label={
                                        vehicle.mdc_rate_card.suggested
                                            ? "MDC rate card (suggested)"
                                            : "MDC rate card"
                                    }
                                >
                                    {vehicle.mdc_rate_card.category_name}
                                    <span className="block font-mono text-xs font-normal text-muted-foreground tabular-nums">
                                        {formatMoney(
                                            vehicle.mdc_rate_card
                                                .rate_per_100km,
                                            "N$",
                                        )}{" "}
                                        per 100km
                                    </span>
                                </Detail>
                            )}
                        </DetailCard>

                        <DetailCard title="Compliance & expiry dates">
                            <ExpiryDetail
                                label="Insurance expiry"
                                expiry={vehicle.insurance_expiry}
                            />
                            <ExpiryDetail
                                label="License disc expiry"
                                expiry={vehicle.disc_expiry}
                            />
                            <ExpiryDetail
                                label="Roadworthy expiry"
                                expiry={vehicle.roadworthy_expiry}
                            />
                            {vehicle.next_service_date && (
                                <ExpiryDetail
                                    label="Next service date"
                                    expiry={vehicle.next_service_date}
                                    pastLabel="Overdue"
                                    soonLabel="Due soon"
                                    extra={
                                        !!vehicle.next_service_mileage && (
                                            <p className="text-xs text-muted-foreground">
                                                or at{" "}
                                                {formatNumber(
                                                    vehicle.next_service_mileage,
                                                )}{" "}
                                                km
                                            </p>
                                        )
                                    }
                                />
                            )}
                        </DetailCard>

                        {vehicle.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap text-foreground/80">
                                        {vehicle.notes}
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right column: related data */}
                    <div className="space-y-6">
                        <VehicleRecentList
                            title="Recent bookings"
                            items={bookings}
                            viewAllHref={bookingsIndex.url({ query: { vehicle: vehicle.id } })}
                            empty="No bookings yet"
                            renderItem={(booking) => ({
                                key: booking.id,
                                main: (
                                    <>
                                        <p className="font-mono font-medium">
                                            {booking.booking_number}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {booking.client}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(booking.start_date)}
                                        </p>
                                    </>
                                ),
                                aside: (
                                    <StatusBadge
                                        tone={
                                            bookingTone[booking.status] ??
                                            "gray"
                                        }
                                    >
                                        {ucfirst(booking.status)}
                                    </StatusBadge>
                                ),
                            })}
                        />

                        <VehicleRecentList
                            title="Recent logbook entries"
                            items={logbooks}
                            viewAllHref={
                                logbookIndex({ query: { vehicle: vehicle.id } })
                                    .url
                            }
                            empty="No logbook entries yet"
                            renderItem={(logbook) => ({
                                key: logbook.id,
                                main: (
                                    <>
                                        <p className="font-medium">
                                            {logbook.origin_from} →{" "}
                                            {logbook.origin_to}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {logbook.driver}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(logbook.date)} •{" "}
                                            {formatNumber(logbook.distance)} km
                                        </p>
                                    </>
                                ),
                            })}
                        />

                        <VehicleRecentList
                            title="Recent MDC charges"
                            items={mdcCalculations}
                            viewAllHref={mdcIndex.url({ query: { vehicle: vehicle.id } })}
                            empty="No MDC charges yet"
                            renderItem={(mdc) => ({
                                key: mdc.id,
                                main: (
                                    <>
                                        <p className="font-mono font-medium tabular-nums">
                                            {formatMoney(mdc.mdc_amount, "N$")}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatNumber(mdc.distance_km)} km
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(mdc.calculation_date)}
                                        </p>
                                    </>
                                ),
                                aside: (
                                    <StatusBadge
                                        tone={
                                            paymentTone[mdc.payment_status] ??
                                            "gray"
                                        }
                                    >
                                        {humanize(mdc.payment_status)}
                                    </StatusBadge>
                                ),
                            })}
                        />

                        {inspections.length > 0 && (
                            <VehicleRecentList
                                title="Recent inspections"
                                items={inspections}
                                renderItem={(inspection) => ({
                                    key: inspection.id,
                                    main: (
                                        <>
                                            <p className="font-medium">
                                                {inspection.inspection_type}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {inspection.inspector ?? "N/A"}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(
                                                    inspection.inspection_date,
                                                )}
                                            </p>
                                        </>
                                    ),
                                    aside: (
                                        <StatusBadge
                                            tone={
                                                inspection.passed
                                                    ? "green"
                                                    : "red"
                                            }
                                        >
                                            {inspection.passed
                                                ? "Passed"
                                                : "Failed"}
                                        </StatusBadge>
                                    ),
                                })}
                            />
                        )}
                    </div>
                </div>
            </PageContainer>
        </AppLayout>
    );
}

function DetailCard({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-2 gap-4">{children}</div>
            </CardContent>
        </Card>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <span className="text-sm text-muted-foreground">{label}</span>
            <div className="font-medium">{children}</div>
        </div>
    );
}

function ExpiryDetail({
    label,
    expiry,
    pastLabel = "Expired",
    soonLabel = "Expiring soon",
    extra,
}: {
    label: string;
    expiry: Expiry;
    pastLabel?: string;
    soonLabel?: string;
    extra?: ReactNode;
}) {
    return (
        <div className="space-y-1">
            <span className="text-sm text-muted-foreground">{label}</span>
            {expiry ? (
                <>
                    <p
                        className={
                            expiry.past
                                ? "font-medium text-destructive"
                                : expiry.soon
                                  ? "font-medium text-warning"
                                  : "font-medium"
                        }
                    >
                        {formatDate(expiry.date)}
                    </p>
                    {expiry.past && (
                        <StatusBadge tone="red">{pastLabel}</StatusBadge>
                    )}
                    {expiry.soon && (
                        <StatusBadge tone="yellow">{soonLabel}</StatusBadge>
                    )}
                    {extra}
                </>
            ) : (
                <p className="text-muted-foreground/70">Not set</p>
            )}
        </div>
    );
}
