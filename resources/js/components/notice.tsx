import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type NoticeTone = 'success' | 'warning' | 'error' | 'info';

const TONE: Record<NoticeTone, { border: string; wash: string; title: string; dot: string }> = {
    success: { border: 'border-success', wash: 'bg-(--nx-pos-wash)', title: 'text-success', dot: 'bg-success' },
    warning: { border: 'border-warning', wash: 'bg-(--nx-warn-wash)', title: 'text-warning', dot: 'bg-warning' },
    error: { border: 'border-destructive', wash: 'bg-(--nx-neg-wash)', title: 'text-destructive', dot: 'bg-destructive' },
    info: { border: 'border-info', wash: 'bg-(--nx-info-wash)', title: 'text-info', dot: 'bg-info' },
};

/**
 * Nexus inline notice: feedback that stays on the page (unlike a toast). A toned wash with a
 * hairline and a dot, a bold title in the tone, body in ink. Say what happened and what to do.
 */
export function Notice({
    tone = 'info',
    title,
    children,
    action,
    className,
}: {
    tone?: NoticeTone;
    title?: ReactNode;
    children?: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    const t = TONE[tone];

    return (
        <div role="status" className={cn('flex items-start gap-4 rounded-md border px-5 py-4', t.border, t.wash, className)}>
            <span aria-hidden="true" className={cn('mt-[7px] size-[5px] shrink-0 rounded-full', t.dot)} />
            <div className="min-w-0 flex-1">
                {title && <div className={cn('text-body font-bold', t.title, children && 'mb-1')}>{title}</div>}
                {children && <div className="text-body text-pretty text-foreground">{children}</div>}
            </div>
            {action && <div className="shrink-0">{action}</div>}
        </div>
    );
}
