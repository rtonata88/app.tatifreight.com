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

/* A Nexus rail row: flush to the rail edge, 2px accent bar and accent wash when active. */
const ROW =
    'h-auto rounded-none border-l-2 border-transparent px-[18px] py-3 md:py-2 text-body font-medium text-sidebar-foreground data-[active=true]:border-primary data-[active=true]:bg-sidebar-accent data-[active=true]:font-semibold [&>svg]:opacity-70 data-[active=true]:[&>svg]:opacity-100';

export function useIsActive() {
    const { currentUrl, isCurrentUrl } = useCurrentUrl();

    return (item: NavItem) =>
        isCurrentUrl(item.href) ||
        (item.activePrefixes ?? []).some((prefix) => currentUrl === prefix || currentUrl.startsWith(`${prefix}/`));
}

export function NavMain({ items = [], title = 'Platform' }: { items: NavItem[]; title?: string }) {
    const isActive = useIsActive();

    return (
        <SidebarGroup className="px-0 py-2">
            {title && (
                <SidebarGroupLabel className="h-auto rounded-none px-5 pb-2 text-[10px] font-semibold tracking-label text-muted-foreground uppercase">
                    {title}
                </SidebarGroupLabel>
            )}
            <SidebarMenu className="gap-0">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={isActive(item)} tooltip={{ children: item.title }} className={ROW}>
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon strokeWidth={1.6} />}
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
