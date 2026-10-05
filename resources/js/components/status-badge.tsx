import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type BadgeTone = 'green' | 'blue' | 'yellow' | 'red' | 'gray' | 'zinc' | 'purple' | 'orange' | 'amber' | 'indigo' | 'cyan' | 'lime' | 'emerald' | 'sky' | 'pink';

/*
 * The Nexus palette has four state colours, so the old Flux colour names fold onto them:
 * green → success, amber/yellow → warning, red → danger, blue → info, and the rest neutral.
 */
const tones: Record<BadgeTone, string> = {
    green: 'border-success text-success',
    emerald: 'border-success text-success',
    lime: 'border-success text-success',
    yellow: 'border-warning text-warning',
    amber: 'border-warning text-warning',
    orange: 'border-warning text-warning',
    red: 'border-destructive text-destructive',
    pink: 'border-destructive text-destructive',
    blue: 'border-info text-info',
    sky: 'border-info text-info',
    cyan: 'border-info text-info',
    indigo: 'border-primary text-primary',
    purple: 'border-primary text-primary',
    gray: 'border-muted-foreground text-muted-foreground',
    zinc: 'border-muted-foreground text-muted-foreground',
};

/**
 * Nexus status pill: record state only. Uppercase, tracked, dot-led, a hairline pill in the
 * state's colour, sized to sit inside a 36px table row. Counts and categories use Badge instead.
 */
export function StatusBadge({ tone = 'gray', children, className }: { tone?: BadgeTone; children: ReactNode; className?: string }) {
    return (
        <span
            data-slot="status-pill"
            className={cn(
                'inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border bg-transparent px-2 py-px text-[10px] leading-[1.5] font-semibold tracking-[0.06em] whitespace-nowrap uppercase [&>svg]:size-3',
                tones[tone] ?? tones.gray,
                className,
            )}
        >
            <span aria-hidden="true" className="size-1 shrink-0 rounded-full bg-current" />
            {children}
        </span>
    );
}
