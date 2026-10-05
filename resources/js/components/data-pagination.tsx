import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

/**
 * Pagination for a Laravel paginator. Keeps the current query string
 * (filters, search) because Laravel's links already include it when the
 * controller calls ->withQueryString().
 */
export function DataPagination<T>({ paginator, className }: { paginator: Paginated<T>; className?: string }) {
    if (paginator.last_page <= 1) {
        return paginator.total > 0 ? (
            <p className={cn('text-sm text-muted-foreground', className)}>
                Showing {paginator.from} to {paginator.to} of {paginator.total} results
            </p>
        ) : null;
    }

    const links = paginator.links.slice(1, -1);

    return (
        <div className={cn('flex flex-col items-center justify-between gap-3 sm:flex-row', className)}>
            <p className="text-sm text-muted-foreground">
                Showing {paginator.from} to {paginator.to} of {paginator.total} results
            </p>
            <nav className="flex flex-wrap items-center gap-1" aria-label="Pagination">
                <PageLink href={paginator.prev_page_url} label="Previous">
                    <ChevronLeft className="size-4" />
                </PageLink>
                {links.map((link, index) =>
                    link.url === null ? (
                        <span key={`gap-${index}`} className="px-2 text-sm text-muted-foreground">
                            …
                        </span>
                    ) : (
                        <PageLink key={link.label} href={link.url} active={link.active} label={`Page ${link.label}`}>
                            {link.label}
                        </PageLink>
                    ),
                )}
                <PageLink href={paginator.next_page_url} label="Next">
                    <ChevronRight className="size-4" />
                </PageLink>
            </nav>
        </div>
    );
}

function PageLink({
    href,
    active = false,
    label,
    children,
}: {
    href: string | null;
    active?: boolean;
    label: string;
    children: React.ReactNode;
}) {
    const classes = cn(
        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 font-mono text-sm tabular-nums transition-colors',
        active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary hover:text-primary',
        !href && 'pointer-events-none opacity-50',
    );

    if (!href) {
        return (
            <span className={classes} aria-label={label} aria-disabled>
                {children}
            </span>
        );
    }

    return (
        <Link href={href} preserveScroll preserveState className={classes} aria-label={label} aria-current={active ? 'page' : undefined}>
            {children}
        </Link>
    );
}
