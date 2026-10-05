import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { Toaster } from '@/components/ui/sonner';
import { initializeTheme } from '@/hooks/use-appearance';
import { registerFlashToasts } from '@/hooks/use-flash-toast';
import type { Flash } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
                {/* One toaster for the whole app, so toasts survive page changes. */}
                <Toaster position="bottom-right" mobileOffset={{ bottom: 'calc(4.5rem + env(safe-area-inset-bottom))' }} />
            </StrictMode>,
        );

        registerFlashToasts(props.initialPage.props.flash as Flash | undefined);
    },
    progress: {
        // Nexus accent (Slate theme).
        color: '#4e73df',
    },
});

// This will set light / dark mode on load...
initializeTheme();
