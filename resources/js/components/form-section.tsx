import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/** A titled card holding a group of form fields (the old <flux:card> + heading). */
export function FormSection({
    title,
    description,
    children,
    className,
    columns = 2,
}: {
    title: string;
    description?: ReactNode;
    children: ReactNode;
    className?: string;
    /** Grid columns from the md breakpoint up. */
    columns?: 1 | 2 | 3;
}) {
    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent>
                <div className={cn('grid grid-cols-1 gap-6', columns === 2 && 'md:grid-cols-2', columns === 3 && 'md:grid-cols-3')}>{children}</div>
            </CardContent>
        </Card>
    );
}
