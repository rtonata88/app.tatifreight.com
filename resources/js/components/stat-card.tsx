import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type StatTone = 'neutral' | 'positive' | 'warning' | 'negative' | 'info' | 'accent';

const TONE: Record<StatTone, string> = {
    neutral: 'text-foreground',
    positive: 'text-success',
    warning: 'text-warning',
    negative: 'text-destructive',
    info: 'text-info',
    accent: 'text-primary',
};

/**
 * Nexus metric card: label as an eyebrow, the figure in condensed tabular numerals, a muted
 * meta line beneath. Format the value before passing it in. Never put an icon in a metric card;
 * colour the figure only when it carries state (owed, overdue, paid).
 */
export function StatCard({
    label,
    value,
    hint,
    tone = 'neutral',
    emphasis = false,
    className,
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    tone?: StatTone;
    /** The single most important figure on the page: accent hairline and a faint wash. */
    emphasis?: boolean;
    className?: string;
}) {
    return (
        <div
            data-slot="metric-card"
            className={cn(
                'flex min-w-0 flex-col gap-2 rounded-lg border bg-card px-4 pt-4 pb-3 shadow-lift md:gap-3 md:px-5 md:pt-5 md:pb-4',
                emphasis && 'border-(--nx-rule-brass) bg-[radial-gradient(circle_at_top_right,var(--nx-brass-wash),transparent_65%),var(--card)]',
                className,
            )}
        >
            <div className="text-micro font-semibold tracking-label text-pretty text-muted-foreground uppercase">{label}</div>
            <div className={cn('font-condensed text-[22px] leading-none break-words md:text-[28px] font-bold tracking-[-0.01em] tabular-nums', TONE[tone])}>{value}</div>
            {hint && <div className="min-w-0 truncate text-xs text-muted-foreground">{hint}</div>}
        </div>
    );
}
