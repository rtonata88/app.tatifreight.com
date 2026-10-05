import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import type { NavGroup, NavItem } from '@/types';

function useIsActive() {
    const { currentUrl, isCurrentUrl } = useCurrentUrl();

    return (item: NavItem) =>
        isCurrentUrl(item.href) ||
        (item.activePrefixes ?? []).some((prefix) => currentUrl === prefix || currentUrl.startsWith(`${prefix}/`));
}

export function NavMain({ items = [], title = 'Platform' }: { items: NavItem[]; title?: string }) {
    const isActive = useIsActive();

    return (
        <SidebarGroup className="px-2 py-0">
            {title && <SidebarGroupLabel>{title}</SidebarGroupLabel>}
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={isActive(item)} tooltip={{ children: item.title }}>
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

/** Several labelled groups, skipping any group whose items were all filtered out. */
export function NavGroups({ groups }: { groups: NavGroup[] }) {
    return (
        <>
            {groups
                .filter((group) => group.items.length > 0)
                .map((group) => (
                    <NavMain key={group.title} title={group.title} items={group.items} />
                ))}
        </>
    );
}

export { toUrl };
