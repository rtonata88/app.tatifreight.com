import type { BadgeTone } from '@/components/status-badge';

/** An expense category option; the server sends the list (they live in the expense_categories table). */
export type ExpenseCategory = { value: string; label: string };

/** The category's label, or the stored value as the old views showed it when it is not in the list. */
export function categoryLabel(categories: ExpenseCategory[], value: string): string {
    return categories.find((c) => c.value === value)?.label ?? ucfirst(value);
}

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
