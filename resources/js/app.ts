import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

function resolveAppName(): string {
    const dataPage = document.getElementById('app')?.getAttribute('data-page');

    if (dataPage) {
        try {
            const name = JSON.parse(dataPage)?.props?.name;

            if (typeof name === 'string' && name !== '') {
                return name;
            }
        } catch {
            // Fall back to the build-time application name below.
        }
    }

    return import.meta.env.VITE_APP_NAME || 'Laravel';
}

const appName = resolveAppName();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        return AppLayout;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
