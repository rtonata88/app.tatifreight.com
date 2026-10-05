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
import AppLogo from '@/components/app-logo';
import { NavGroups } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import type { NavGroup, NavItem } from '@/types';

/**
 * Same menu, groups and permission checks as the old Flux sidebar.
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
            title: 'Fleet Management',
            items: [
                ...only(can('view-vehicles'), { title: 'Vehicles', href: '/vehicles', icon: Truck, activePrefixes: ['/vehicles'] }),
                ...only(can('view-logbook'), { title: 'Logbook', href: '/logbook', icon: BookOpen, activePrefixes: ['/logbook'] }),
                ...only(can('view-mdc'), { title: 'MDC Charges', href: '/mdc', icon: Wallet }),
                ...only(can('manage-mdc-rates'), { title: 'MDC Rates', href: '/mdc-rates', icon: Calculator, activePrefixes: ['/mdc-rates'] }),
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
                ...only(can('view-rate-cards'), { title: 'Rate Cards', href: '/rate-cards', icon: BadgeDollarSign, activePrefixes: ['/rate-cards'] }),
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
                      { title: 'Profit & Loss', href: '/reports/profit-loss', icon: FileBarChart },
                      { title: 'VAT Report', href: '/reports/vat', icon: FileText },
                      { title: 'MDC Report', href: '/reports/mdc', icon: FileText },
                  ]
                : [],
        },
        {
            title: 'Administration',
            items: hasRole('admin')
                ? [
                      { title: 'Users', href: '/users', icon: UserCog, activePrefixes: ['/users'] },
                      { title: 'Roles & Permissions', href: '/roles', icon: ShieldCheck, activePrefixes: ['/roles'] },
                      { title: 'Company Settings', href: '/settings/company', icon: Building2, activePrefixes: ['/settings/bank-accounts'] },
                  ]
                : [],
        },
    ];

    const homeUrl = can('view-reports') ? '/reports/dashboard' : '/bookings';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavGroups groups={groups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
