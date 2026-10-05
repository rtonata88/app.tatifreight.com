import { usePage } from '@inertiajs/react';

/**
 * Read the signed-in user's permissions and roles on the client.
 * Only use this to show or hide UI — the server always re-checks.
 */
export function usePermissions() {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const roles = auth?.roles ?? [];

    const can = (permission: string) => permissions.includes(permission);
    const canAny = (...list: string[]) => list.some((p) => permissions.includes(p));
    const hasRole = (role: string) => roles.includes(role);

    return { can, canAny, hasRole, permissions, roles };
}
