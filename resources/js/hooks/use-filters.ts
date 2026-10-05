import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Keep list filters (search, status, dates…) in the URL query string, the way
 * Livewire's wire:model.live did. Text fields are debounced; every change
 * resets to page 1.
 *
 * const { filters, setFilter } = useFilters(index().url, props.filters);
 * <Input value={filters.search} onChange={(e) => setFilter('search', e.target.value)} />
 */
export function useFilters<T extends Record<string, string | number | boolean | null | undefined>>(
    url: string,
    initial: T,
    { debounce = 300, only }: { debounce?: number; only?: string[] } = {},
) {
    const [filters, setFilters] = useState<T>(initial);
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timer = setTimeout(() => {
            const query: Record<string, string | number | boolean> = {};
            for (const [key, value] of Object.entries(filters)) {
                if (value !== '' && value !== null && value !== undefined && value !== false) {
                    query[key] = value;
                }
            }

            router.get(url, query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                ...(only ? { only } : {}),
            });
        }, debounce);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [JSON.stringify(filters)]);

    const setFilter = <K extends keyof T>(key: K, value: T[K]) => setFilters((current) => ({ ...current, [key]: value }));

    const reset = (values: Partial<T> = {}) => setFilters({ ...(Object.fromEntries(Object.keys(filters).map((k) => [k, ''])) as T), ...values });

    return { filters, setFilter, setFilters, reset };
}
