import type { Auth } from '@/types/auth';

export type Company = {
    name: string;
    logo_url: string | null;
};

export type Flash = {
    success?: string | null;
    error?: string | null;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            company: Company;
            auth: Auth;
            flash: Flash;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
