import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Icon + label for a table row action. In desktop tables (`compact`) only the
 * icon shows, with the label kept for screen readers and as a hover title;
 * in the phone card layout the label is visible.
 */
export function RowActionContent({ icon: Icon, label, compact }: { icon: LucideIcon; label: ReactNode; compact: boolean }) {
    return (
        <>
            <Icon />
            <span className={cn(compact && 'sr-only')}>{label}</span>
        </>
    );
}

/** Button props for a row action: icon-sized in tables, full size in phone cards. */
export function rowActionProps(compact: boolean, label: string, className?: string) {
    return compact
        ? { size: 'icon' as const, className: cn('size-8', className), title: label, 'aria-label': label }
        : { size: 'sm' as const, className };
}
