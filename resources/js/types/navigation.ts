import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /**
     * URL prefixes that also mark this item active, e.g. ['/vehicles'] keeps
     * "Vehicles" highlighted on /vehicles/12/edit.
     */
    activePrefixes?: string[];
};

export type NavGroup = {
    title: string;
    items: NavItem[];
};
