import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Ellipsis, List, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { BookingDetailsDialog, type CalendarBooking } from '@/components/bookings/booking-details-dialog';
import { calendarChipClass, calendarStatusTone } from '@/components/bookings/booking-status';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, humanize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { calendar, create, index } from '@/routes/bookings';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    /** Y-m-d inside the month being shown. */
    date: string;
    today: string;
    bookings: CalendarBooking[];
    vehicles: Option[];
    filters: { vehicle: string };
    can: { create: boolean; edit: boolean };
};

type Day = {
    key: string;
    day: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    bookings: CalendarBooking[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bookings', href: index() },
    { title: 'Calendar', href: calendar() },
];

const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const LEGEND = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

const pad = (n: number) => String(n).padStart(2, '0');
const ymd = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

/** Monday-first weeks covering the whole month, like startOfWeek()/endOfWeek() in the Volt component. */
function buildWeeks(year: number, month: number, today: string, bookings: CalendarBooking[]): Day[][] {
    const first = new Date(year, month, 1);
    const last = new Date(year, month + 1, 0);
    const start = new Date(first);
    start.setDate(first.getDate() - ((first.getDay() + 6) % 7));
    const end = new Date(last);
    end.setDate(last.getDate() + ((7 - last.getDay()) % 7));

    const weeks: Day[][] = [];
    let week: Day[] = [];
    for (const day = new Date(start); day <= end; day.setDate(day.getDate() + 1)) {
        const key = ymd(day);
        week.push({
            key,
            day: day.getDate(),
            isCurrentMonth: day.getMonth() === month,
            isToday: key === today,
            // A booking shows on every date from its start day to its end day.
            bookings: bookings.filter((b) => b.start_date <= key && b.end_date >= key),
        });
        if (week.length === 7) {
            weeks.push(week);
            week = [];
        }
    }

    return weeks;
}

export default function BookingsCalendar({ date, today, bookings, vehicles, filters, can }: Props) {
    const [viewType, setViewType] = useState<'month' | 'list'>('month');
    const [selected, setSelected] = useState<CalendarBooking | null>(null);

    const [year, month] = date.split('-').map(Number);
    const monthIndex = month - 1;
    const weeks = useMemo(() => buildWeeks(year, monthIndex, today, bookings), [year, monthIndex, today, bookings]);

    const visit = (query: { date?: string; vehicle?: string }) => {
        const next = { date, vehicle: filters.vehicle, ...query };
        router.get(calendar().url, Object.fromEntries(Object.entries(next).filter(([, value]) => value)), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const shiftMonth = (offset: number) => visit({ date: ymd(new Date(year, monthIndex + offset, 1)) });

    const clientLabel = (b: CalendarBooking) => b.client.company_name || b.client.name;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Booking Calendar" />
            <PageContainer>
                <PageHeader
                    title="Vehicle Booking Calendar"
                    actions={
                        <>
                            <div className="hidden gap-3 md:flex">
                                <Button asChild variant="ghost">
                                    <Link href={index()}>
                                        <List /> List View
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
                                            <Link href={index()}>
                                                <List /> List View
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

                {/* Controls */}
                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" size="icon" className="size-8" onClick={() => shiftMonth(-1)} aria-label="Previous month">
                                <ChevronLeft />
                            </Button>
                            <div className="min-w-[160px] text-center text-lg font-semibold sm:min-w-[200px]">
                                {MONTHS[monthIndex]} {year}
                            </div>
                            <Button variant="ghost" size="icon" className="size-8" onClick={() => shiftMonth(1)} aria-label="Next month">
                                <ChevronRight />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => visit({ date: '' })}>
                                Today
                            </Button>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <div className="min-w-[200px]">
                                <NativeSelect
                                    value={filters.vehicle}
                                    onChange={(e) => visit({ vehicle: e.target.value })}
                                    aria-label="Filter by vehicle"
                                >
                                    <option value="">All Vehicles</option>
                                    {vehicles.map((vehicle) => (
                                        <option key={vehicle.value} value={vehicle.value}>
                                            {vehicle.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>

                            <div className="flex gap-1 rounded bg-muted p-1">
                                {(['month', 'list'] as const).map((type) => (
                                    <button
                                        key={type}
                                        type="button"
                                        onClick={() => setViewType(type)}
                                        className={cn(
                                            'rounded px-3 py-1 text-sm',
                                            viewType === type ? 'bg-background shadow' : 'text-muted-foreground',
                                        )}
                                    >
                                        {type === 'month' ? 'Month' : 'List'}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {viewType === 'month' ? (
                    <Card>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[700px] table-fixed border-collapse">
                                    <thead>
                                        <tr className="bg-muted">
                                            {WEEKDAYS.map((weekday) => (
                                                <th key={weekday} className="border px-2 py-2 text-center text-sm font-semibold">
                                                    {weekday}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {weeks.map((week) => (
                                            <tr key={week[0].key}>
                                                {week.map((day) => (
                                                    <td
                                                        key={day.key}
                                                        className={cn(
                                                            'h-32 border p-1 align-top',
                                                            !day.isCurrentMonth && 'bg-muted/50',
                                                            day.isToday && 'bg-blue-50 dark:bg-blue-500/10',
                                                        )}
                                                    >
                                                        <div
                                                            className={cn(
                                                                'mb-1 text-sm font-medium',
                                                                !day.isCurrentMonth
                                                                    ? 'text-muted-foreground/60'
                                                                    : day.isToday
                                                                      ? 'text-blue-600 dark:text-blue-400'
                                                                      : '',
                                                            )}
                                                        >
                                                            {pad(day.day)}
                                                        </div>
                                                        <div className="space-y-1">
                                                            {day.bookings.map((booking) => (
                                                                <button
                                                                    key={booking.id}
                                                                    type="button"
                                                                    onClick={() => setSelected(booking)}
                                                                    className={cn(
                                                                        'block w-full cursor-pointer rounded px-1 py-0.5 text-left text-xs hover:opacity-80',
                                                                        calendarChipClass[booking.status],
                                                                    )}
                                                                    title={`${clientLabel(booking)} - ${booking.vehicle.reg_number} (${booking.vehicle.type})`}
                                                                >
                                                                    <div className="truncate font-semibold">{booking.vehicle.reg_number}</div>
                                                                    <div className="truncate text-[10px] opacity-75">{booking.vehicle.type}</div>
                                                                    <div className="truncate">{clientLabel(booking)}</div>
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Legend */}
                            <div className="mt-4 flex flex-wrap gap-4 text-xs">
                                {LEGEND.map((status) => (
                                    <div key={status} className="flex items-center gap-1">
                                        <div className={cn('size-3', calendarChipClass[status])} />
                                        <span>{humanize(status)}</span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Booking #</TableHead>
                                        <TableHead>Vehicle</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Start Date</TableHead>
                                        <TableHead>End Date</TableHead>
                                        <TableHead>Duration</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bookings.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                                No bookings found for this period.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        bookings.map((booking) => (
                                            <TableRow key={booking.id}>
                                                <TableCell>
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelected(booking)}
                                                        className="text-blue-600 hover:underline dark:text-blue-400"
                                                    >
                                                        {booking.booking_number}
                                                    </button>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{booking.vehicle.reg_number}</div>
                                                    <div className="text-sm text-muted-foreground">{booking.vehicle.type}</div>
                                                </TableCell>
                                                <TableCell>{clientLabel(booking)}</TableCell>
                                                <TableCell>{formatDate(booking.start_date)}</TableCell>
                                                <TableCell>{formatDate(booking.end_date)}</TableCell>
                                                <TableCell>{booking.duration_days} days</TableCell>
                                                <TableCell>
                                                    <StatusBadge tone={calendarStatusTone[booking.status] ?? 'gray'}>
                                                        {humanize(booking.status)}
                                                    </StatusBadge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </PageContainer>

            <BookingDetailsDialog booking={selected} canEdit={can.edit} onClose={() => setSelected(null)} />
        </AppLayout>
    );
}
