import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarDays,
    Check,
    CheckCircle2,
    CircleCheck,
    Clock,
    Ellipsis,
    MapPin,
    Pencil,
    Play,
    Plus,
    Search,
    Trash2,
    XCircle,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { bookingStatusTone } from '@/components/bookings/booking-status';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataPagination } from '@/components/data-pagination';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatNumber, humanize } from '@/lib/format';
import { calendar, create, destroy, edit, index, status as statusRoute } from '@/routes/bookings';
import type { BreadcrumbItem, Paginated } from '@/types';

type BookingRow = {
    id: number;
    booking_number: string;
    status: string;
    client: {
        name: string | null;
        phone: string | null;
        company_name: string | null;
    };
    vehicle: { reg_number: string | null; type: string | null };
    driver: string | null;
    pickup_location: string | null;
    delivery_location: string | null;
    distance_km: number | null;
    load_weight: number | null;
    cargo_description: string | null;
    cargo_excerpt: string | null;
    start_date: string | null;
    end_date: string | null;
};

type Props = {
    bookings: Paginated<BookingRow>;
    stats: {
        pending: number;
        confirmed: number;
        in_progress: number;
        completed: number;
    };
    filters: { search: string; status: string; date: string };
    can: { create: boolean; edit: boolean; delete: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bookings', href: index() }];

const EMPTY_MESSAGE = 'No bookings found. Create your first booking to get started.';
const DELETE_MESSAGE = 'Are you sure you want to delete this booking?';

export default function BookingsIndex({ bookings, stats, filters: initialFilters, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);
    const [pendingDelete, setPendingDelete] = useState<BookingRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const updateStatus = (booking: BookingRow, status: string) => router.patch(statusRoute(booking.id).url, { status }, { preserveScroll: true });

    const deleteBooking = (booking: BookingRow, done: () => void) =>
        router.delete(destroy(booking.id).url, {
            preserveScroll: true,
            onFinish: done,
        });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Booking Management" />
            <PageContainer>
                <PageHeader
                    title="Booking Management"
                    actions={
                        <>
                            <div className="hidden gap-3 md:flex">
                                <Button asChild variant="ghost">
                                    <Link href={calendar()}>
                                        <CalendarDays /> Calendar View
                                    </Link>
                                </Button>
                                {can.create && (
                                    <Button asChild>
                                        <Link href={create()}>
                                            <Plus /> New Booking
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <div className="md:hidden">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="icon" aria-label="Actions">
                                            <Ellipsis />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="min-w-40">
                                        <DropdownMenuItem asChild>
                                            <Link href={calendar()}>
                                                <CalendarDays /> Calendar View
                                            </Link>
                                        </DropdownMenuItem>
                                        {can.create && (
                                            <DropdownMenuItem asChild>
                                                <Link href={create()}>
                                                    <Plus /> New Booking
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </>
                    }
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <StatCard
                        label="Pending"
                        value={stats.pending}
                        icon={Clock}
                        className="bg-yellow-50 dark:bg-yellow-500/10"
                        valueClassName="text-yellow-700 dark:text-yellow-300"
                    />
                    <StatCard
                        label="Confirmed"
                        value={stats.confirmed}
                        icon={CheckCircle2}
                        className="bg-blue-50 dark:bg-blue-500/10"
                        valueClassName="text-blue-700 dark:text-blue-300"
                    />
                    <StatCard
                        label="In Progress"
                        value={stats.in_progress}
                        icon={Zap}
                        className="bg-purple-50 dark:bg-purple-500/10"
                        valueClassName="text-purple-700 dark:text-purple-300"
                    />
                    <StatCard
                        label="Completed"
                        value={stats.completed}
                        icon={Check}
                        className="bg-green-50 dark:bg-green-500/10"
                        valueClassName="text-green-700 dark:text-green-300"
                    />
                </div>

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) => setFilter('search', e.target.value)}
                                    placeholder="Search bookings, clients, vehicles..."
                                />
                            </div>
                            <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)} aria-label="Filter by status">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </NativeSelect>
                            <NativeSelect value={filters.date} onChange={(e) => setFilter('date', e.target.value)} aria-label="Filter by date">
                                <option value="">All Dates</option>
                                <option value="today">Today</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="past">Past</option>
                            </NativeSelect>
                        </div>

                        {/* Phones: cards */}
                        <div className="space-y-4 md:hidden">
                            {bookings.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">{EMPTY_MESSAGE}</div>
                            ) : (
                                bookings.data.map((booking) => (
                                    <div key={booking.id} className="space-y-3 rounded-lg border p-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <div className="text-lg font-bold">{booking.booking_number}</div>
                                                <div className="text-sm text-muted-foreground">{booking.client.name}</div>
                                                {booking.client.phone && (
                                                    <div className="text-xs text-muted-foreground">
                                                        <a
                                                            href={`tel:${booking.client.phone}`}
                                                            className="hover:text-blue-600 dark:hover:text-blue-400"
                                                        >
                                                            {booking.client.phone}
                                                        </a>
                                                    </div>
                                                )}
                                                {booking.client.company_name && (
                                                    <div className="text-xs text-muted-foreground">{booking.client.company_name}</div>
                                                )}
                                            </div>
                                            <StatusBadge tone={bookingStatusTone[booking.status] ?? 'gray'}>{humanize(booking.status)}</StatusBadge>
                                        </div>

                                        {(booking.pickup_location || booking.delivery_location) && (
                                            <div className="grid grid-cols-1 gap-2 border-t pt-3">
                                                {booking.pickup_location && (
                                                    <div className="flex items-start gap-2">
                                                        <MapPin className="mt-0.5 size-5 shrink-0 text-green-600 dark:text-green-400" />
                                                        <div>
                                                            <div className="text-xs text-muted-foreground">Pickup</div>
                                                            <div className="text-sm font-medium">{booking.pickup_location}</div>
                                                        </div>
                                                    </div>
                                                )}
                                                {booking.delivery_location && (
                                                    <div className="flex items-start gap-2">
                                                        <MapPin className="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                                                        <div>
                                                            <div className="text-xs text-muted-foreground">Delivery</div>
                                                            <div className="text-sm font-medium">{booking.delivery_location}</div>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                            <div>
                                                <div className="text-xs text-muted-foreground">Vehicle</div>
                                                <div className="text-sm font-medium">{booking.vehicle.reg_number}</div>
                                                <div className="text-xs text-muted-foreground">{booking.vehicle.type}</div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">Driver</div>
                                                {booking.driver ? (
                                                    <div className="text-sm font-medium">{booking.driver}</div>
                                                ) : (
                                                    <div className="text-sm text-muted-foreground">Not assigned</div>
                                                )}
                                            </div>
                                            {!!booking.distance_km && (
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Distance</div>
                                                    <div className="text-sm font-medium">{formatNumber(booking.distance_km, 2)} km</div>
                                                </div>
                                            )}
                                            {!!booking.load_weight && (
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Load Weight</div>
                                                    <div className="text-sm font-medium">{formatNumber(booking.load_weight, 2)} tons</div>
                                                </div>
                                            )}
                                        </div>

                                        {booking.cargo_description && (
                                            <div className="border-t pt-3">
                                                <div className="text-xs text-muted-foreground">Cargo</div>
                                                <div className="text-sm">{booking.cargo_description}</div>
                                            </div>
                                        )}

                                        <div className="border-t pt-3">
                                            <div className="text-xs text-muted-foreground">Duration</div>
                                            <div className="text-sm">
                                                {formatDate(booking.start_date)} - {formatDate(booking.end_date)}
                                            </div>
                                        </div>

                                        {(can.edit || can.delete) && (
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                {can.edit && (
                                                    <>
                                                        {booking.status === 'pending' && (
                                                            <Button size="sm" className="flex-1" onClick={() => updateStatus(booking, 'confirmed')}>
                                                                Confirm
                                                            </Button>
                                                        )}
                                                        {booking.status === 'confirmed' && (
                                                            <Button size="sm" className="flex-1" onClick={() => updateStatus(booking, 'in_progress')}>
                                                                Start
                                                            </Button>
                                                        )}
                                                        {booking.status === 'in_progress' && (
                                                            <Button size="sm" className="flex-1" onClick={() => updateStatus(booking, 'completed')}>
                                                                Complete
                                                            </Button>
                                                        )}
                                                        <Button asChild size="sm" variant="ghost" className="flex-1">
                                                            <Link href={edit(booking.id)}>
                                                                <Pencil /> Edit
                                                            </Link>
                                                        </Button>
                                                    </>
                                                )}
                                                {can.delete && (
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button size="sm" variant="destructive">
                                                                <Trash2 /> Delete
                                                            </Button>
                                                        }
                                                        description={DELETE_MESSAGE}
                                                        onConfirm={(done) => deleteBooking(booking, done)}
                                                    />
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Tablets and up: table */}
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Booking #</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Vehicle</TableHead>
                                        <TableHead>Route</TableHead>
                                        <TableHead>Distance</TableHead>
                                        <TableHead>Load Details</TableHead>
                                        <TableHead>Dates</TableHead>
                                        <TableHead>Driver</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bookings.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={10} className="py-8 text-center text-muted-foreground">
                                                {EMPTY_MESSAGE}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        bookings.data.map((booking) => (
                                            <TableRow key={booking.id}>
                                                <TableCell className="font-bold">{booking.booking_number}</TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{booking.client.name}</div>
                                                    {booking.client.phone && (
                                                        <div className="text-xs text-muted-foreground">{booking.client.phone}</div>
                                                    )}
                                                    {booking.client.company_name && (
                                                        <div className="text-sm text-muted-foreground">{booking.client.company_name}</div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{booking.vehicle.reg_number}</div>
                                                    <div className="text-sm text-muted-foreground">{booking.vehicle.type}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm whitespace-normal">
                                                        {booking.pickup_location && (
                                                            <div className="font-medium">From: {booking.pickup_location}</div>
                                                        )}
                                                        {booking.delivery_location && (
                                                            <div className="text-muted-foreground">To: {booking.delivery_location}</div>
                                                        )}
                                                        {!booking.pickup_location && !booking.delivery_location && (
                                                            <span className="text-muted-foreground">Not specified</span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {booking.distance_km ? (
                                                        <div className="text-sm font-medium">{formatNumber(booking.distance_km, 2)} km</div>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">N/A</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {booking.load_weight || booking.cargo_description ? (
                                                        <div className="text-sm">
                                                            {!!booking.load_weight && (
                                                                <div className="font-medium">{formatNumber(booking.load_weight, 2)} tons</div>
                                                            )}
                                                            {booking.cargo_excerpt && (
                                                                <div className="text-muted-foreground">{booking.cargo_excerpt}</div>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">N/A</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        <div>{formatDate(booking.start_date)}</div>
                                                        <div className="text-muted-foreground">to {formatDate(booking.end_date)}</div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {booking.driver ? (
                                                        <div className="text-sm">{booking.driver}</div>
                                                    ) : (
                                                        <span className="text-muted-foreground">Not assigned</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge tone={bookingStatusTone[booking.status] ?? 'gray'}>
                                                        {humanize(booking.status)}
                                                    </StatusBadge>
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button size="icon" variant="ghost" className="size-8" aria-label="Booking actions">
                                                                <Ellipsis />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="start" className="min-w-32">
                                                            {can.edit && (
                                                                <>
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={edit(booking.id)}>
                                                                            <Pencil /> Edit
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    {booking.status === 'pending' && (
                                                                        <DropdownMenuItem onSelect={() => updateStatus(booking, 'confirmed')}>
                                                                            <CircleCheck /> Confirm Booking
                                                                        </DropdownMenuItem>
                                                                    )}
                                                                    {booking.status === 'confirmed' && (
                                                                        <DropdownMenuItem onSelect={() => updateStatus(booking, 'in_progress')}>
                                                                            <Play /> Start Trip
                                                                        </DropdownMenuItem>
                                                                    )}
                                                                    {booking.status === 'in_progress' && (
                                                                        <DropdownMenuItem onSelect={() => updateStatus(booking, 'completed')}>
                                                                            <Check /> Complete Trip
                                                                        </DropdownMenuItem>
                                                                    )}
                                                                    {['pending', 'confirmed'].includes(booking.status) && (
                                                                        <DropdownMenuItem onSelect={() => updateStatus(booking, 'cancelled')}>
                                                                            <XCircle /> Cancel Booking
                                                                        </DropdownMenuItem>
                                                                    )}
                                                                    <DropdownMenuSeparator />
                                                                </>
                                                            )}
                                                            {can.delete && (
                                                                <DropdownMenuItem variant="destructive" onSelect={() => setPendingDelete(booking)}>
                                                                    <Trash2 /> Delete
                                                                </DropdownMenuItem>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <DataPagination paginator={bookings} />
                    </CardContent>
                </Card>
            </PageContainer>

            {/* Delete confirmation for the table's dropdown (the menu closes before the dialog opens). */}
            <AlertDialog open={pendingDelete !== null} onOpenChange={(open) => !open && !deleting && setPendingDelete(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>{DELETE_MESSAGE}</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={deleting}>Cancel</AlertDialogCancel>
                        <Button
                            variant="destructive"
                            disabled={deleting}
                            onClick={() => {
                                if (!pendingDelete) return;
                                setDeleting(true);
                                deleteBooking(pendingDelete, () => {
                                    setDeleting(false);
                                    setPendingDelete(null);
                                });
                            }}
                        >
                            Delete
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
