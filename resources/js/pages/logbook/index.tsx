import { Head, Link, router } from "@inertiajs/react";
import {
    BookOpen,
    ClipboardList,
    MapPin,
    Pencil,
    Plus,
    Search,
    Trash2,
    TrendingUp,
} from "lucide-react";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { DataPagination } from "@/components/data-pagination";
import { EmptyState } from "@/components/empty-state";
import { FormField } from "@/components/form-field";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatCard } from "@/components/stat-card";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { useFilters } from "@/hooks/use-filters";
import AppLayout from "@/layouts/app-layout";
import { formatDate, formatNumber } from "@/lib/format";
import { create, destroy, edit, index } from "@/routes/logbook";
import type { BreadcrumbItem, Option, Paginated } from "@/types";

type LogbookRow = {
    id: number;
    date: string | null;
    vehicle_reg: string | null;
    vehicle_type: string | null;
    driver: string | null;
    origin_from: string;
    origin_to: string;
    start_odometer: number;
    end_odometer: number | null;
    distance: number;
};

type Filters = {
    search: string;
    vehicle: string;
    dateFrom: string;
    dateTo: string;
};

type Props = {
    logbooks: Paginated<LogbookRow>;
    vehicles: Option[];
    totalDistance: number;
    totalTrips: number;
    filters: Filters;
    can: { create: boolean; edit: boolean; delete: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: "Logbook", href: index() }];

const EMPTY_MESSAGE =
    "No logbook entries found. Add your first entry to get started.";

export default function LogbookIndex({
    logbooks,
    vehicles,
    totalDistance,
    totalTrips,
    filters: initialFilters,
    can,
}: Props) {
    // `filtered` marks that the user has touched the filters, so a cleared date stays cleared
    // instead of falling back to the current month (the old screen's starting range).
    const { filters, setFilter } = useFilters(index().url, {
        ...initialFilters,
        filtered: "1",
    });

    const deleteLogbook = (logbook: LogbookRow, done: () => void) =>
        router.delete(destroy(logbook.id).url, {
            preserveScroll: true,
            onFinish: done,
        });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Logbook" />
            <PageContainer>
                <PageHeader
                    title="Vehicle Logbook"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New Entry
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <StatCard
                        label="Total Distance"
                        value={`${formatNumber(totalDistance)} km`}
                        icon={TrendingUp}
                        valueClassName="text-3xl text-blue-700 dark:text-blue-300"
                    />
                    <StatCard
                        label="Total Trips"
                        value={formatNumber(totalTrips)}
                        icon={ClipboardList}
                        valueClassName="text-3xl text-green-700 dark:text-green-300"
                    />
                </div>

                <Card>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <FormField label="Search" htmlFor="search">
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        className="pl-9"
                                        value={filters.search}
                                        onChange={(e) =>
                                            setFilter("search", e.target.value)
                                        }
                                        placeholder="Search vehicle, driver, location..."
                                    />
                                </div>
                            </FormField>
                            <FormField label="Vehicle" htmlFor="vehicle">
                                <NativeSelect
                                    id="vehicle"
                                    value={filters.vehicle}
                                    onChange={(e) =>
                                        setFilter("vehicle", e.target.value)
                                    }
                                >
                                    <option value="">All Vehicles</option>
                                    {vehicles.map((vehicle) => (
                                        <option
                                            key={vehicle.value}
                                            value={vehicle.value}
                                        >
                                            {vehicle.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField label="Date From" htmlFor="dateFrom">
                                <Input
                                    id="dateFrom"
                                    type="date"
                                    value={filters.dateFrom}
                                    onChange={(e) =>
                                        setFilter("dateFrom", e.target.value)
                                    }
                                />
                            </FormField>
                            <FormField label="Date To" htmlFor="dateTo">
                                <Input
                                    id="dateTo"
                                    type="date"
                                    value={filters.dateTo}
                                    onChange={(e) =>
                                        setFilter("dateTo", e.target.value)
                                    }
                                />
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="space-y-4">
                        {logbooks.data.length === 0 ? (
                            <EmptyState icon={BookOpen} title={EMPTY_MESSAGE} />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {logbooks.data.map((logbook) => (
                                        <div
                                            key={logbook.id}
                                            className="space-y-3 rounded-lg border p-4"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <div className="text-lg font-bold">
                                                        {formatDate(
                                                            logbook.date,
                                                        )}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {logbook.vehicle_reg} -{" "}
                                                        {logbook.vehicle_type}
                                                    </div>
                                                </div>
                                                {logbook.distance > 0 && (
                                                    <div className="text-right">
                                                        <div className="text-sm text-muted-foreground">
                                                            Distance
                                                        </div>
                                                        <div className="text-lg font-bold text-blue-700 dark:text-blue-300">
                                                            {formatNumber(
                                                                logbook.distance,
                                                            )}{" "}
                                                            km
                                                        </div>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="space-y-1 border-t pt-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="size-4 text-muted-foreground" />
                                                    <span className="font-medium">
                                                        {logbook.origin_from}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="ml-1 size-4 text-muted-foreground" />
                                                    <span className="font-medium">
                                                        {logbook.origin_to}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Driver
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {logbook.driver}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Vehicle
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {logbook.vehicle_reg}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {logbook.vehicle_type}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Start Odometer
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {formatNumber(
                                                            logbook.start_odometer,
                                                        )}{" "}
                                                        km
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        End Odometer
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {logbook.end_odometer
                                                            ? `${formatNumber(logbook.end_odometer)} km`
                                                            : "-"}
                                                    </div>
                                                </div>
                                            </div>

                                            {(can.edit || can.delete) && (
                                                <div className="flex flex-wrap gap-2 border-t pt-3">
                                                    <RowActions
                                                        logbook={logbook}
                                                        can={can}
                                                        onDelete={deleteLogbook}
                                                        stretch
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Vehicle</TableHead>
                                                <TableHead>Driver</TableHead>
                                                <TableHead>Route</TableHead>
                                                <TableHead className="text-right">
                                                    Start Odometer
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    End Odometer
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Distance
                                                </TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {logbooks.data.map((logbook) => (
                                                <TableRow key={logbook.id}>
                                                    <TableCell className="font-medium">
                                                        {formatDate(
                                                            logbook.date,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="font-medium">
                                                            {
                                                                logbook.vehicle_reg
                                                            }
                                                        </div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {
                                                                logbook.vehicle_type
                                                            }
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {logbook.driver}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div>
                                                                <strong>
                                                                    From:
                                                                </strong>{" "}
                                                                {
                                                                    logbook.origin_from
                                                                }
                                                            </div>
                                                            <div>
                                                                <strong>
                                                                    To:
                                                                </strong>{" "}
                                                                {
                                                                    logbook.origin_to
                                                                }
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatNumber(
                                                            logbook.start_odometer,
                                                        )}{" "}
                                                        km
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {logbook.end_odometer
                                                            ? `${formatNumber(logbook.end_odometer)} km`
                                                            : "-"}
                                                    </TableCell>
                                                    <TableCell className="text-right font-semibold">
                                                        {logbook.distance > 0
                                                            ? `${formatNumber(logbook.distance)} km`
                                                            : "-"}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <RowActions
                                                                logbook={
                                                                    logbook
                                                                }
                                                                can={can}
                                                                onDelete={
                                                                    deleteLogbook
                                                                }
                                                            />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={logbooks} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function RowActions({
    logbook,
    can,
    onDelete,
    stretch = false,
}: {
    logbook: LogbookRow;
    can: Props["can"];
    onDelete: (logbook: LogbookRow, done: () => void) => void;
    stretch?: boolean;
}) {
    const className = stretch ? "flex-1" : undefined;

    return (
        <>
            {can.edit && (
                <Button asChild size="sm" variant="ghost" className={className}>
                    <Link href={edit(logbook.id)}>
                        <Pencil /> Edit
                    </Link>
                </Button>
            )}
            {can.delete && (
                <ConfirmDialog
                    trigger={
                        <Button
                            size="sm"
                            variant="destructive"
                            className={className}
                        >
                            <Trash2 /> Delete
                        </Button>
                    }
                    description="Are you sure you want to delete this logbook entry?"
                    onConfirm={(done) => onDelete(logbook, done)}
                />
            )}
        </>
    );
}
