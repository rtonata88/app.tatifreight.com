import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import type { Flash } from '@/types';

function show(flash: Flash | undefined) {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
}

/**
 * Show the server's flash messages (session 'success' / 'error') as toasts.
 * Registered once at boot (not per page), so each message shows exactly once —
 * on the first page load and after every Inertia visit, even when the same
 * message comes twice in a row.
 */
export function registerFlashToasts(initialFlash: Flash | undefined) {
    // Let the toaster mount before showing the first page's message.
    setTimeout(() => show(initialFlash), 0);
    router.on('success', (event) => show(event.detail.page.props.flash as Flash | undefined));
}
