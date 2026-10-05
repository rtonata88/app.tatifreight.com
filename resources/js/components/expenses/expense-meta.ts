import type { BadgeTone } from '@/components/status-badge';

/** Category options, as listed in the old selects. */
export const expenseCategories: { value: string; label: string }[] = [
    { value: 'fuel', label: 'Fuel' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'repairs', label: 'Repairs' },
    { value: 'tolls', label: 'Tolls' },
    { value: 'insurance', label: 'Insurance' },
    { value: 'licenses', label: 'Licenses' },
    { value: 'wages', label: 'Driver wages' },
    { value: 'other', label: 'Other' },
];

export const expenseStatusTone: Record<string, BadgeTone> = {
    pending: 'yellow',
    approved: 'green',
    rejected: 'red',
};

/** PHP ucfirst(): the old views showed category/status this way. */
export function ucfirst(value: string | null | undefined): string {
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1);
}
