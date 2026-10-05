import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Nexus page body: 32px gutter (16px on phones), 32px between sections, capped at 1440px. */
export function PageContainer({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('mx-auto flex w-full max-w-[1440px] min-w-0 flex-1 flex-col gap-8 p-4 md:p-8', className)}>{children}</div>;
}
