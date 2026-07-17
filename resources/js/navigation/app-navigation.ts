import { BookOpen, FolderGit2, LayoutGrid, Settings } from '@lucide/vue';
import { dashboard } from '@/routes';
import { index as settingsIndex } from '@/routes/settings';
import type { NavigationDefinition } from '@/types/navigation';

/**
 * The canonical primary navigation definition. This is the single source of
 * truth for the application's main navigation destinations — sidebar and
 * header adapters resolve this definition rather than declaring their own.
 */
export const primary: readonly NavigationDefinition[] = [
    {
        type: 'item',
        id: 'dashboard',
        label: 'navigation.dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        type: 'item',
        id: 'settings',
        label: 'settings.sidebar',
        href: settingsIndex(),
        icon: Settings,
        ability: 'manage_settings',
    },
];

/**
 * The canonical secondary navigation definition: external links surfaced
 * alongside the primary navigation.
 */
export const secondary: readonly NavigationDefinition[] = [
    {
        type: 'item',
        id: 'repository',
        label: 'navigation.repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
        isExternal: true,
    },
    {
        type: 'item',
        id: 'documentation',
        label: 'navigation.documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
        isExternal: true,
    },
];
