import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

function toAppName(value: unknown): string | null {
    return typeof value === 'string' && value !== '' ? value : null;
}

function resolveInitialAppName(): string {
    const dataPage = document.getElementById('app')?.getAttribute('data-page');

    if (dataPage) {
        try {
            const name = toAppName(JSON.parse(dataPage)?.props?.name);

            if (name !== null) {
                return name;
            }
        } catch {
            // Fall back to the build-time application name below.
        }
    }

    return import.meta.env.VITE_APP_NAME || 'Laravel';
}

// Track the shared site name so the document title stays in sync when it
// changes via an Inertia visit (e.g. after updating General Settings).
let appName = resolveInitialAppName();

router.on('success', (event) => {
    appName = toAppName(event.detail.page.props.name) ?? appName;
});

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
