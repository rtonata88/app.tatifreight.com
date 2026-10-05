import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    className,
    valueClassName,
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    icon?: LucideIcon;
    className?: string;
    valueClassName?: string;
}) {
    return (
        <Card className={cn('gap-0 py-0', className)}>
            <CardContent className="flex items-start justify-between gap-3 p-4">
                <div className="min-w-0 space-y-1">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className={cn('truncate text-2xl font-semibold tracking-tight', valueClassName)}>{value}</p>
                    {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
                </div>
                {Icon && (
                    <div className="rounded-md bg-muted p-2 text-muted-foreground">
                        <Icon className="size-5" />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
