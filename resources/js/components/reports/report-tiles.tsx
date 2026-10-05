import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Report tones fold onto the Nexus state colours: green → success, red → destructive,
 * orange/yellow → warning, blue → info, purple → accent, gray → neutral.
 */
export type ReportTone = 'green' | 'red' | 'blue' | 'orange' | 'purple' | 'yellow' | 'gray';

export const toneSurface: Record<ReportTone, string> = {
    green: 'bg-(--nx-pos-wash) border-success',
    red: 'bg-(--nx-neg-wash) border-destructive',
    blue: 'bg-(--nx-info-wash) border-info',
    orange: 'bg-(--nx-warn-wash) border-warning',
    purple: 'bg-(--nx-brass-wash-2) border-primary',
    yellow: 'bg-(--nx-warn-wash) border-warning',
    gray: 'bg-muted border-border',
};

export const toneText: Record<ReportTone, string> = {
    green: 'text-success',
    red: 'text-destructive',
    blue: 'text-info',
    orange: 'text-warning',
    purple: 'text-primary',
    yellow: 'text-warning',
    gray: 'text-foreground',
};

/**
 * A row inside a breakdown card: label (+ optional sub-label) on the left, amount on the right.
 * Ordinary lines stay neutral; the emphasised total carries the tone.
 */
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
                'flex items-center justify-between gap-4 border',
                emphasis ? cn('rounded-md p-4', toneSurface[tone]) : 'border-transparent border-b-border px-3 py-2.5',
                className,
            )}
        >
            <div className="min-w-0">
                <div className={cn(emphasis ? 'font-bold' : 'text-sm font-medium')}>{label}</div>
                {sublabel && <div className="text-xs text-muted-foreground">{sublabel}</div>}
            </div>
            <span
                className={cn(
                    'shrink-0 text-right font-mono tabular-nums',
                    emphasis ? cn('text-base font-bold', toneText[tone]) : 'text-sm font-medium text-foreground',
                )}
            >
                {value}
            </span>
        </div>
    );
}
