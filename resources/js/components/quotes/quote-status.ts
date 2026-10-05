import type { BadgeTone } from '@/components/status-badge';

/** Badge colours from the old <flux:badge :color="match($quote->status)">. */
export const quoteStatusTone: Record<string, BadgeTone> = {
    draft: 'gray',
    sent: 'blue',
    approved: 'green',
    rejected: 'red',
    expired: 'orange',
};
