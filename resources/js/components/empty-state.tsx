import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

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
        <div className={cn('flex flex-col items-center justify-center gap-2 px-4 py-12 text-center', className)}>
            {Icon && <Icon className="size-10 text-muted-foreground/60" />}
            <p className="font-medium">{title}</p>
            {description && <p className="max-w-sm text-sm text-muted-foreground">{description}</p>}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
