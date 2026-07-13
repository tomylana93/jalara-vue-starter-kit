import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const fallbackAppName = import.meta.env.VITE_APP_NAME || 'Laravel';

function toAppName(value: unknown): string {
    return typeof value === 'string' && value !== '' ? value : fallbackAppName;
}

createInertiaApp({
    // Read the shared site name from the live page on every title render so
    // the suffix reflects an updated name immediately, even on the same visit
    // that saved it (e.g. after updating General Settings).
    title: (title, page) => {
        const appName = toAppName(page?.props?.name);

        return title ? `${title} - ${appName}` : appName;
    },
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
