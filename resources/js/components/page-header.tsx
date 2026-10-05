import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PageHeaderProps = {
    title: string;
    description?: ReactNode;
    /** Small uppercase module name above the title. */
    eyebrow?: string;
    /** Buttons shown on the right (stack under the title on phones). At most one primary. */
    actions?: ReactNode;
    className?: string;
};

/** Nexus page header: the first element in every page body, closed by a hairline. */
export function PageHeader({ title, description, eyebrow, actions, className }: PageHeaderProps) {
    return (
        <header className={cn('flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6', className)}>
            <div className="min-w-0">
                {eyebrow && <div className="mb-2.5 text-micro font-semibold tracking-label text-muted-foreground uppercase">{eyebrow}</div>}
                <h1 className="text-2xl leading-tight font-bold tracking-[-0.015em] text-pretty sm:text-[28px]">{title}</h1>
                {description && <p className="mt-2 max-w-[620px] text-sm text-pretty text-muted-foreground">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2.5">{actions}</div>}
        </header>
    );
}
