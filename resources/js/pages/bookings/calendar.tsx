import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, List, Plus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { BookingDetailsDialog, type CalendarBooking } from '@/components/bookings/booking-details-dialog';
import { calendarChipClass, calendarStatusTone } from '@/components/bookings/booking-status';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

/** Solid dot per booking in the phone month grid (chips are too wide at 390px). */
const DOT_CLASS: Record<string, string> = {
    pending: 'bg-warning',
    confirmed: 'bg-info',
    in_progress: 'bg-success',
    completed: 'bg-muted-foreground',
    cancelled: 'bg-destructive',
};

const isPhone = () => typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches;

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
    // Phones open on the list: a seven-column month grid only has room for dots there.
    const [viewType, setViewType] = useState<'month' | 'list'>(() => (isPhone() ? 'list' : 'month'));
    const [selected, setSelected] = useState<CalendarBooking | null>(null);
    /** Y-m-d picked by tapping a day in the phone month grid; narrows the list to that day. */
    const [dayFilter, setDayFilter] = useState<string | null>(null);

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

    const shiftMonth = (offset: number) => {
        setDayFilter(null);
        visit({ date: ymd(new Date(year, monthIndex + offset, 1)) });
    };

    const showDay = (key: string) => {
        setDayFilter(key);
        setViewType('list');
    };

    const listBookings = dayFilter ? bookings.filter((b) => b.start_date <= dayFilter && b.end_date >= dayFilter) : bookings;

    const clientLabel = (b: CalendarBooking) => b.client.company_name || b.client.name;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle booking calendar" />
            <PageContainer>
                <PageHeader
                    title="Vehicle booking calendar"
                    actions={
                        <>
                            <Button asChild variant="ghost" className="hidden md:inline-flex">
                                <Link href={index()}>
                                    <List /> List view
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="icon" className="md:hidden" aria-label="Bookings list">
                                <Link href={index()}>
                                    <List />
                                </Link>
                            </Button>
                            {can.create && (
                                <Button asChild>
                                    <Link href={create()}>
                                        <Plus /> New booking
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {/* Controls */}
                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-11 md:size-8"
                                onClick={() => shiftMonth(-1)}
                                aria-label="Previous month"
                            >
                                <ChevronLeft />
                            </Button>
                            <div className="min-w-[160px] text-center text-lg font-semibold sm:min-w-[200px]">
                                {MONTHS[monthIndex]} {year}
                            </div>
                            <Button variant="ghost" size="icon" className="size-11 md:size-8" onClick={() => shiftMonth(1)} aria-label="Next month">
                                <ChevronRight />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setDayFilter(null);
                                    visit({ date: '' });
                                }}
                            >
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
                                    <option value="">All vehicles</option>
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
                                        onClick={() => {
                                            setViewType(type);
                                            setDayFilter(null);
                                        }}
                                        className={cn(
                                            'rounded px-3 py-2 text-sm md:py-1',
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
                                <table className="w-full table-fixed border-collapse md:min-w-[700px]">
                                    <thead>
                                        <tr className="bg-muted">
                                            {WEEKDAYS.map((weekday) => (
                                                <th key={weekday} className="border px-1 py-2 text-center text-sm font-semibold md:px-2">
                                                    <abbr title={weekday} className="no-underline md:hidden">
                                                        {weekday[0]}
                                                    </abbr>
                                                    <span className="hidden md:inline">{weekday}</span>
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
                                                            'h-14 border p-0 align-top md:h-32 md:p-1',
                                                            !day.isCurrentMonth && 'bg-muted/50',
                                                            day.isToday && 'bg-(--nx-brass-wash-2)',
                                                        )}
                                                    >
                                                        {/* Phones: the whole cell is a button that lists that day's bookings. */}
                                                        <button
                                                            type="button"
                                                            onClick={() => showDay(day.key)}
                                                            className="flex h-full min-h-14 w-full flex-col items-center gap-1 p-1 md:hidden"
                                                            aria-label={`${formatDate(day.key)}: ${day.bookings.length} booking${day.bookings.length === 1 ? '' : 's'}`}
                                                        >
                                                            <span
                                                                className={cn(
                                                                    'text-sm font-medium',
                                                                    !day.isCurrentMonth
                                                                        ? 'text-muted-foreground/60'
                                                                        : day.isToday
                                                                          ? 'text-primary'
                                                                          : '',
                                                                )}
                                                            >
                                                                {day.day}
                                                            </span>
                                                            {day.bookings.length > 0 && (
                                                                <span className="flex flex-wrap justify-center gap-0.5">
                                                                    {day.bookings.map((booking) => (
                                                                        <span
                                                                            key={booking.id}
                                                                            className={cn(
                                                                                'size-1.5 rounded-full',
                                                                                DOT_CLASS[booking.status] ?? 'bg-muted-foreground',
                                                                            )}
                                                                        />
                                                                    ))}
                                                                </span>
                                                            )}
                                                        </button>
                                                        <div className="hidden md:block">
                                                            <div
                                                                className={cn(
                                                                    'mb-1 text-sm font-medium',
                                                                    !day.isCurrentMonth
                                                                        ? 'text-muted-foreground/60'
                                                                        : day.isToday
                                                                          ? 'text-primary'
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
                                                                        <div className="truncate font-mono font-semibold">
                                                                            {booking.vehicle.reg_number}
                                                                        </div>
                                                                        <div className="truncate text-[10px] opacity-75">{booking.vehicle.type}</div>
                                                                        <div className="truncate">{clientLabel(booking)}</div>
                                                                    </button>
                                                                ))}
                                                            </div>
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
                        <CardContent className="space-y-4">
                            {dayFilter && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    Showing <span className="font-medium text-foreground">{formatDate(dayFilter)}</span>
                                    <Button variant="ghost" size="sm" onClick={() => setDayFilter(null)}>
                                        <X /> Show all
                                    </Button>
                                </div>
                            )}

                            {/* Phones: cards; the whole card opens the booking details. */}
                            <div className="space-y-3 md:hidden">
                                {listBookings.length === 0 ? (
                                    <div className="py-8 text-center text-muted-foreground">
                                        {dayFilter ? 'No bookings on this day.' : 'No bookings found for this period.'}
                                    </div>
                                ) : (
                                    listBookings.map((booking) => (
                                        <button
                                            key={booking.id}
                                            type="button"
                                            onClick={() => setSelected(booking)}
                                            className="block w-full space-y-3 rounded-lg border bg-card p-4 text-left hover:bg-muted/50"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <div className="font-mono font-semibold">{booking.booking_number}</div>
                                                    <div className="truncate text-sm text-muted-foreground">{clientLabel(booking)}</div>
                                                </div>
                                                <StatusBadge tone={calendarStatusTone[booking.status] ?? 'gray'}>
                                                    {humanize(booking.status)}
                                                </StatusBadge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-x-4 gap-y-2 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Vehicle</div>
                                                    <div className="font-mono text-sm font-medium">{booking.vehicle.reg_number}</div>
                                                    <div className="text-xs text-muted-foreground">{booking.vehicle.type}</div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Duration</div>
                                                    <div className="text-sm font-medium">{booking.duration_days} days</div>
                                                </div>
                                                <div className="col-span-2">
                                                    <div className="text-xs text-muted-foreground">Dates</div>
                                                    <div className="text-sm">
                                                        {formatDate(booking.start_date)} - {formatDate(booking.end_date)}
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    ))
                                )}
                            </div>

                            <div className="hidden md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Booking #</TableHead>
                                            <TableHead>Vehicle</TableHead>
                                            <TableHead>Client</TableHead>
                                            <TableHead>Start date</TableHead>
                                            <TableHead>End date</TableHead>
                                            <TableHead>Duration</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {listBookings.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                                    {dayFilter ? 'No bookings on this day.' : 'No bookings found for this period.'}
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            listBookings.map((booking) => (
                                                <TableRow key={booking.id}>
                                                    <TableCell>
                                                        <button
                                                            type="button"
                                                            onClick={() => setSelected(booking)}
                                                            className="font-mono text-primary hover:underline"
                                                        >
                                                            {booking.booking_number}
                                                        </button>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="font-mono font-medium">{booking.vehicle.reg_number}</div>
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
                            </div>
                        </CardContent>
                    </Card>
                )}
            </PageContainer>

            <BookingDetailsDialog booking={selected} canEdit={can.edit} onClose={() => setSelected(null)} />
        </AppLayout>
    );
}
