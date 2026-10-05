/**
 * Shared display formatters. The business operates in Namibia, so money is
 * shown in Namibian dollars (N$) with two decimals, matching the old views.
 */

export function formatMoney(value: number | string | null | undefined, prefix = 'N$ '): string {
    const amount = Number(value ?? 0);
    return (
        prefix +
        amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

export function formatNumber(value: number | string | null | undefined, decimals = 0): string {
    const amount = Number(value ?? 0);
    return amount.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function parseDate(value: string | Date): Date | null {
    if (value instanceof Date) return value;
    // Plain dates ("2025-11-04") are parsed as local dates, not UTC midnight.
    const plain = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    const date = plain ? new Date(Number(plain[1]), Number(plain[2]) - 1, Number(plain[3])) : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

/** "04 Nov 2025" — the PHP format('d M Y') used across the old views. */
export function formatDate(value: string | Date | null | undefined, fallback = '—'): string {
    if (!value) return fallback;
    const date = parseDate(value);
    if (!date) return fallback;
    return `${String(date.getDate()).padStart(2, '0')} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

/** "04 Nov 2025 14:30" */
export function formatDateTime(value: string | Date | null | undefined, fallback = '—'): string {
    if (!value) return fallback;
    const date = parseDate(value);
    if (!date) return fallback;
    return `${formatDate(date)} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

/** "in_use" -> "In use" */
export function humanize(value: string | null | undefined): string {
    if (!value) return '';
    const text = value.replace(/[_-]+/g, ' ').trim();
    return text.charAt(0).toUpperCase() + text.slice(1);
}

/** "in_use" -> "In Use" (ucwords) */
export function titleCase(value: string | null | undefined): string {
    if (!value) return '';
    return value
        .replace(/[_-]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (c) => c.toUpperCase());
}
