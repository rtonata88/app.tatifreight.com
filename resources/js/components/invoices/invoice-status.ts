import type { BadgeTone } from '@/components/status-badge';

/** Badge colours from the old <flux:badge :color="match($invoice->status)">. */
export const invoiceStatusTone: Record<string, BadgeTone> = {
    draft: 'gray',
    sent: 'blue',
    unpaid: 'yellow',
    partial: 'orange',
    paid: 'green',
    overdue: 'red',
};

export const INVOICE_STATUSES = [
    { value: 'draft', label: 'Draft' },
    { value: 'sent', label: 'Sent' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partial', label: 'Partial' },
    { value: 'paid', label: 'Paid' },
    { value: 'overdue', label: 'Overdue' },
];

/** ucfirst($invoice->status) */
export const statusLabel = (status: string) => status.charAt(0).toUpperCase() + status.slice(1);
