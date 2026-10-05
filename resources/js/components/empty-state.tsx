import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Nexus empty state for empty tables and filtered-to-nothing lists. The description explains
 * the cause, not the feature. No illustrations: a faint stroke icon at most.
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: {
    icon?: LucideIcon;
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-col items-center justify-center gap-3 px-6 py-16 text-center', className)}>
            {Icon && <Icon strokeWidth={1.6} className="size-7 text-(--nx-fg-4)" />}
            <div>
                <p className="text-base font-semibold">{title}</p>
                {description && <p className="mt-1.5 max-w-[380px] text-xs text-pretty text-muted-foreground">{description}</p>}
            </div>
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
