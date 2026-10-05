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
    pending: 'bg-yellow-100 text-yellow-800 border-l-2 border-yellow-500 dark:bg-yellow-500/15 dark:text-yellow-200',
    confirmed: 'bg-blue-100 text-blue-800 border-l-2 border-blue-500 dark:bg-blue-500/15 dark:text-blue-200',
    in_progress: 'bg-green-100 text-green-800 border-l-2 border-green-500 dark:bg-green-500/15 dark:text-green-200',
    completed: 'bg-gray-100 text-gray-800 border-l-2 border-gray-500 dark:bg-gray-500/20 dark:text-gray-200',
    cancelled: 'bg-red-100 text-red-800 border-l-2 border-red-500 dark:bg-red-500/15 dark:text-red-200',
};

export const invoiceStatusTone: Record<string, BadgeTone> = {
    paid: 'green',
    partial: 'yellow',
    unpaid: 'red',
    overdue: 'red',
};

/** ucfirst(), as the calendar printed statuses ("In_progress"). */
export const ucfirst = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
