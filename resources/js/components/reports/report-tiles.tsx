import type { ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * Tinted panels the old report views used (bg-green-50 / text-green-700 …),
 * with dark-mode equivalents.
 */
export type ReportTone = 'green' | 'red' | 'blue' | 'orange' | 'purple' | 'yellow' | 'gray';

export const toneSurface: Record<ReportTone, string> = {
    green: 'bg-green-50 border-green-200 dark:bg-green-500/10 dark:border-green-500/25',
    red: 'bg-red-50 border-red-200 dark:bg-red-500/10 dark:border-red-500/25',
    blue: 'bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/25',
    orange: 'bg-orange-50 border-orange-200 dark:bg-orange-500/10 dark:border-orange-500/25',
    purple: 'bg-purple-50 border-purple-200 dark:bg-purple-500/10 dark:border-purple-500/25',
    yellow: 'bg-yellow-50 border-yellow-200 dark:bg-yellow-500/10 dark:border-yellow-500/25',
    gray: 'bg-muted/60 border-border',
};

export const toneText: Record<ReportTone, string> = {
    green: 'text-green-700 dark:text-green-400',
    red: 'text-red-700 dark:text-red-400',
    blue: 'text-blue-700 dark:text-blue-400',
    orange: 'text-orange-700 dark:text-orange-400',
    purple: 'text-purple-700 dark:text-purple-400',
    yellow: 'text-yellow-700 dark:text-yellow-400',
    gray: 'text-foreground',
};

/** Headline summary card (label, big value, small hint) on a tinted background. */
export function MetricCard({
    tone,
    label,
    value,
    hint,
    size = 'md',
}: {
    tone: ReportTone;
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    size?: 'md' | 'lg';
}) {
    return (
        <Card className={cn('gap-0 py-0 shadow-none', toneSurface[tone])}>
            <CardContent className="space-y-1 p-4 md:p-5">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className={cn('font-bold tracking-tight tabular-nums', size === 'lg' ? 'text-2xl md:text-3xl' : 'text-2xl', toneText[tone])}>
                    {value}
                </p>
                {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            </CardContent>
        </Card>
    );
}

/** A row inside a breakdown card: label (+ optional sub-label) on the left, amount on the right. */
export function AmountRow({
    tone,
    label,
    sublabel,
    value,
    emphasis = false,
    className,
}: {
    tone: ReportTone;
    label: ReactNode;
    sublabel?: ReactNode;
    value: ReactNode;
    emphasis?: boolean;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-4 rounded-md border',
                emphasis ? 'border-2 p-4' : 'border-transparent p-3',
                toneSurface[tone],
                className,
            )}
        >
            <div className="min-w-0">
                <div className={cn(emphasis ? 'font-bold' : 'text-sm font-medium')}>{label}</div>
                {sublabel && <div className="text-xs text-muted-foreground">{sublabel}</div>}
            </div>
            <span className={cn('shrink-0 text-right tabular-nums', emphasis ? 'text-lg font-bold' : 'font-semibold', toneText[tone])}>{value}</span>
        </div>
    );
}
