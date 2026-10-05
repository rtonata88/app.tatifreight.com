import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Standard padding and vertical rhythm for every app page. */
export function PageContainer({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('flex h-full flex-1 flex-col gap-6 p-4 md:p-6', className)}>{children}</div>;
}
