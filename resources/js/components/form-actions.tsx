import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * The submit row at the end of a form. On phones it sticks to the bottom of the screen, just
 * above the bottom navigation bar, so Save is always in reach of the thumb on a long form;
 * buttons share the width, primary on the right. From md up it is an ordinary row.
 */
export function FormActions({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <div
            className={cn(
                'sticky bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 -mx-4 flex flex-row-reverse gap-3 border-t bg-background px-4 py-3 *:flex-1',
                'md:static md:mx-0 md:flex-row md:border-0 md:bg-transparent md:p-0 md:*:flex-none',
                className,
            )}
        >
            {children}
        </div>
    );
}
