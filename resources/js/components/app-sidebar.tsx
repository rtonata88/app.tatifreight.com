import { Link } from '@inertiajs/react';
import {
    BadgeDollarSign,
    BarChart3,
    BookOpen,
    Building2,
    Calculator,
    Calendar,
    CalendarDays,
    FileBarChart,
    FileText,
    FolderOpen,
    Receipt,
    ReceiptText,
    ShieldCheck,
    Truck,
    UserCog,
    Users,
    Wallet,
} from 'lucide-react';
import { NavGroups } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Wordmark } from '@/components/wordmark';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import type { NavGroup, NavItem } from '@/types';

/**
 * Same menu, groups and permission checks as the old Flux sidebar, drawn as the Nexus rail.
 * Plain URLs are used so the menu does not depend on generated route files.
 */
export function AppSidebar() {
    const { can, hasRole } = usePermissions();

    const only = (condition: boolean, item: NavItem): NavItem[] => (condition ? [item] : []);

    const groups: NavGroup[] = [
        {
            title: 'Dashboard',
            items: only(can('view-reports'), { title: 'Analytics', href: '/reports/dashboard', icon: BarChart3 }),
        },
        {
            title: 'Fleet management',
            items: [
                ...only(can('view-vehicles'), { title: 'Vehicles', href: '/vehicles', icon: Truck, activePrefixes: ['/vehicles'] }),
                ...only(can('view-logbook'), { title: 'Logbook', href: '/logbook', icon: BookOpen, activePrefixes: ['/logbook'] }),
                ...only(can('view-mdc'), { title: 'MDC charges', href: '/mdc', icon: Wallet }),
                ...only(can('manage-mdc-rates'), { title: 'MDC rates', href: '/mdc-rates', icon: Calculator, activePrefixes: ['/mdc-rates'] }),
            ],
        },
        {
            title: 'Operations',
            items: [
                ...only(can('view-bookings'), { title: 'Bookings', href: '/bookings', icon: Calendar }),
                ...only(can('view-bookings'), { title: 'Calendar', href: '/bookings/calendar', icon: CalendarDays }),
                ...only(can('view-quotes'), { title: 'Quotes', href: '/quotes', icon: FileText, activePrefixes: ['/quotes'] }),
            ],
        },
        {
            title: 'Financial',
            items: [
                ...only(can('view-invoices'), { title: 'Invoices', href: '/invoices', icon: Receipt, activePrefixes: ['/invoices'] }),
                ...only(can('view-expenses'), { title: 'Expenses', href: '/expenses', icon: ReceiptText, activePrefixes: ['/expenses'] }),
                ...only(can('view-rate-cards'), { title: 'Rate cards', href: '/rate-cards', icon: BadgeDollarSign, activePrefixes: ['/rate-cards'] }),
            ],
        },
        {
            title: 'Management',
            items: [
                ...only(can('view-clients'), { title: 'Clients', href: '/clients', icon: Users, activePrefixes: ['/clients'] }),
                ...only(can('view-documents'), { title: 'Documents', href: '/documents', icon: FolderOpen, activePrefixes: ['/documents'] }),
            ],
        },
        {
            title: 'Reports',
            items: can('view-reports')
                ? [
                      { title: 'Profit and loss', href: '/reports/profit-loss', icon: FileBarChart },
                      { title: 'VAT report', href: '/reports/vat', icon: FileText },
                      { title: 'MDC report', href: '/reports/mdc', icon: FileText },
                  ]
                : [],
        },
        {
            title: 'Administration',
            items: hasRole('admin')
                ? [
                      { title: 'Users', href: '/users', icon: UserCog, activePrefixes: ['/users'] },
                      { title: 'Roles and permissions', href: '/roles', icon: ShieldCheck, activePrefixes: ['/roles'] },
                      { title: 'Company settings', href: '/settings/company', icon: Building2, activePrefixes: ['/settings/bank-accounts'] },
                  ]
                : [],
        },
    ];

    const homeUrl = can('view-reports') ? '/reports/dashboard' : '/bookings';

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader className="h-20 justify-center border-b px-5 group-data-[collapsible=icon]:px-2">
                <Link href={homeUrl} prefetch className="flex min-w-0 group-data-[collapsible=icon]:hidden">
                    <Wordmark />
                </Link>
            </SidebarHeader>

            <SidebarContent className="gap-0 py-2">
                <NavGroups groups={groups} />
            </SidebarContent>

            <SidebarFooter className="border-t px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] group-data-[collapsible=icon]:px-2">
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
