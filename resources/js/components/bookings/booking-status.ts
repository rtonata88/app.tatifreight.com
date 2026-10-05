import type { BadgeTone } from '@/components/status-badge';

/** Badge colours used by the bookings list and edit page. */
export const bookingStatusTone: Record<string, BadgeTone> = {
    pending: 'yellow',
    confirmed: 'blue',
    in_progress: 'purple',
    completed: 'green',
    cancelled: 'red',
};

/**
 * The calendar page used its own palette (in progress = green, completed = gray),
 * as described in BOOKING_CALENDAR.md.
 */
export const calendarStatusTone: Record<string, BadgeTone> = {
    pending: 'yellow',
    confirmed: 'blue',
    in_progress: 'green',
    completed: 'gray',
    cancelled: 'red',
};

/** Classes for a booking chip in the month grid (and its legend swatch). */
export const calendarChipClass: Record<string, string> = {
    pending: 'bg-(--nx-warn-wash) text-foreground border-l-2 border-warning',
    confirmed: 'bg-(--nx-info-wash) text-foreground border-l-2 border-info',
    in_progress: 'bg-(--nx-pos-wash) text-foreground border-l-2 border-success',
    completed: 'bg-muted text-muted-foreground border-l-2 border-muted-foreground',
    cancelled: 'bg-(--nx-neg-wash) text-foreground border-l-2 border-destructive',
};

export const invoiceStatusTone: Record<string, BadgeTone> = {
    paid: 'green',
    partial: 'yellow',
    unpaid: 'red',
    overdue: 'red',
};

/** ucfirst(), as the calendar printed statuses ("In_progress"). */
export const ucfirst = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
