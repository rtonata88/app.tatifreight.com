import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * The company's mark: the uploaded logo when there is one, otherwise the company name set in
 * type. A logo that fails to load falls back to the name, so a missing file never shows as a
 * broken image.
 */
export function Wordmark({
    size = 'rail',
    light = false,
    className,
}: {
    size?: 'rail' | 'panel';
    /** Set the name in paper colour, for the dark auth panel. */
    light?: boolean;
    className?: string;
}) {
    const { company } = usePage().props;
    const [broken, setBroken] = useState(false);

    if (company.logo_url && !broken) {
        return (
            <img
                src={company.logo_url}
                alt={company.name}
                onError={() => setBroken(true)}
                className={cn(
                    'w-auto max-w-full object-contain object-left',
                    size === 'rail' ? 'max-h-12' : 'max-h-20',
                    className,
                )}
            />
        );
    }

    return (
        <span
            className={cn(
                'truncate font-bold tracking-[0.02em]',
                size === 'rail' ? 'text-base' : 'text-[26px] font-extrabold',
                light && 'text-[#F2EDE3]',
                className,
            )}
        >
            {company.name}
        </span>
    );
}
