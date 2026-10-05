import type { Auth } from '@/types/auth';

export type Flash = {
    success?: string | null;
    error?: string | null;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: Flash;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
