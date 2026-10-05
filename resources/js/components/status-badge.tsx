import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type BadgeTone = 'green' | 'blue' | 'yellow' | 'red' | 'gray' | 'zinc' | 'purple' | 'orange' | 'amber' | 'indigo' | 'cyan' | 'lime' | 'emerald' | 'sky' | 'pink';

const tones: Record<BadgeTone, string> = {
    green: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-500/15 dark:text-green-300 dark:border-green-500/30',
    emerald: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30',
    lime: 'bg-lime-100 text-lime-800 border-lime-200 dark:bg-lime-500/15 dark:text-lime-300 dark:border-lime-500/30',
    blue: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30',
    sky: 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:border-sky-500/30',
    cyan: 'bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-500/15 dark:text-cyan-300 dark:border-cyan-500/30',
    indigo: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30',
    purple: 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30',
    pink: 'bg-pink-100 text-pink-800 border-pink-200 dark:bg-pink-500/15 dark:text-pink-300 dark:border-pink-500/30',
    yellow: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-500/15 dark:text-yellow-300 dark:border-yellow-500/30',
    amber: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30',
    orange: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-500/15 dark:text-orange-300 dark:border-orange-500/30',
    red: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-500/15 dark:text-red-300 dark:border-red-500/30',
    gray: 'bg-muted text-muted-foreground border-border',
    zinc: 'bg-muted text-muted-foreground border-border',
};

/**
 * Coloured status pill — the replacement for <flux:badge color="…">.
 * Pick the same colour the old view used for each status.
 */
export function StatusBadge({ tone = 'gray', children, className }: { tone?: BadgeTone; children: ReactNode; className?: string }) {
    return (
        <span
            className={cn(
                'inline-flex w-fit shrink-0 items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap [&>svg]:size-3',
                tones[tone] ?? tones.gray,
                className,
            )}
        >
            {children}
        </span>
    );
}
