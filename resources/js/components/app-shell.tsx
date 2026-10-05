import { usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

/* The Nexus rail: 248px expanded, 64px collapsed to icons. */
const RAIL = { '--sidebar-width': '248px', '--sidebar-width-icon': '64px' } as CSSProperties;

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen} style={RAIL}>
            {children}
        </SidebarProvider>
    );
}
