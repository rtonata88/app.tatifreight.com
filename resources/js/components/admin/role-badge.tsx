import { StatusBadge, type BadgeTone } from '@/components/status-badge';

/** Badge colour per role, as the old users and roles screens used. */
export const roleTone: Record<string, BadgeTone> = {
    admin: 'red',
    manager: 'blue',
    accountant: 'green',
    dispatcher: 'purple',
    driver: 'gray',
};

/** PHP ucfirst() */
export const ucfirst = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);

export function RoleBadge({ role, className }: { role: string; className?: string }) {
    return (
        <StatusBadge tone={roleTone[role] ?? 'gray'} className={className}>
            {ucfirst(role)}
        </StatusBadge>
    );
}
