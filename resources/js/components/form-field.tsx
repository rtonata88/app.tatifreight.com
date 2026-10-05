import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type FormFieldProps = {
    label?: ReactNode;
    htmlFor?: string;
    error?: string;
    description?: ReactNode;
    required?: boolean;
    className?: string;
    children: ReactNode;
};

/** Label + control + help text + validation error, laid out consistently. */
export function FormField({ label, htmlFor, error, description, required, className, children }: FormFieldProps) {
    return (
        <div className={cn('grid gap-2', className)}>
            {label && (
                <Label htmlFor={htmlFor}>
                    {label}
                    {required && <span className="text-destructive">*</span>}
                </Label>
            )}
            {children}
            {description && <p className="text-xs text-muted-foreground">{description}</p>}
            <InputError message={error} />
        </div>
    );
}
