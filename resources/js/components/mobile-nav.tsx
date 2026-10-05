import { Link } from '@inertiajs/react';
import { BarChart3, BookOpen, Calendar, FileText, Menu, Receipt, ReceiptText, Truck, Users } from 'lucide-react';
import { useIsActive } from '@/components/nav-main';
import { useSidebar } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

/* Screens in the order they earn a slot in the bar; each user gets the first four they can open. */
const CANDIDATES: (NavItem & { permission: string })[] = [
    { title: 'Dashboard', href: '/reports/dashboard', icon: BarChart3, permission: 'view-reports', activePrefixes: ['/reports'] },
    { title: 'Bookings', href: '/bookings', icon: Calendar, permission: 'view-bookings', activePrefixes: ['/bookings'] },
    { title: 'Logbook', href: '/logbook', icon: BookOpen, permission: 'view-logbook', activePrefixes: ['/logbook'] },
    { title: 'Expenses', href: '/expenses', icon: ReceiptText, permission: 'view-expenses', activePrefixes: ['/expenses'] },
    { title: 'Invoices', href: '/invoices', icon: Receipt, permission: 'view-invoices', activePrefixes: ['/invoices'] },
    { title: 'Vehicles', href: '/vehicles', icon: Truck, permission: 'view-vehicles', activePrefixes: ['/vehicles'] },
    { title: 'Quotes', href: '/quotes', icon: FileText, permission: 'view-quotes', activePrefixes: ['/quotes'] },
    { title: 'Clients', href: '/clients', icon: Users, permission: 'view-clients', activePrefixes: ['/clients'] },
];

const SLOT =
    'flex min-w-0 flex-1 flex-col items-center justify-center gap-1 border-t-2 border-transparent pt-1.5 pb-1 text-[11px] font-medium text-muted-foreground transition-colors duration-200';

/**
 * Phone navigation: a fixed bar along the bottom edge with the user's four main screens and
 * "More", which opens the full menu. Hidden from md up, where the rail is always visible.
 */
export function MobileNav() {
    const { can } = usePermissions();
    const { setOpenMobile } = useSidebar();
    const isActive = useIsActive();
    const items = CANDIDATES.filter((item) => can(item.permission)).slice(0, 4);

    return (
        <nav
            aria-label="Main"
            className="fixed inset-x-0 bottom-0 z-30 flex h-[calc(4rem+env(safe-area-inset-bottom))] border-t bg-background pb-[env(safe-area-inset-bottom)] md:hidden"
        >
            {items.map((item) => {
                const active = isActive(item);

                return (
                    <Link
                        key={item.href.toString()}
                        href={item.href}
                        prefetch
                        aria-current={active ? 'page' : undefined}
                        className={cn(SLOT, active && 'border-primary font-semibold text-primary')}
                    >
                        {item.icon && <item.icon strokeWidth={1.6} className="size-[22px]" />}
                        <span className="truncate">{item.title}</span>
                    </Link>
                );
            })}
            <button type="button" onClick={() => setOpenMobile(true)} className={SLOT}>
                <Menu strokeWidth={1.6} className="size-[22px]" />
                <span>More</span>
            </button>
        </nav>
    );
}
