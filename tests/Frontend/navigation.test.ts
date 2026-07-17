import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { resolveNavigation } from '../../resources/js/lib/navigation.ts';
import type { NavigationDefinition } from '../../resources/js/types/navigation.ts';

const appNavigationPath = new URL(
    '../../resources/js/navigation/app-navigation.ts',
    import.meta.url,
);

function buildDefinitions(): NavigationDefinition[] {
    return [
        {
            type: 'item',
            id: 'dashboard',
            label: 'navigation.dashboard',
            href: '/dashboard',
        },
        {
            type: 'group',
            id: 'settings',
            label: 'settings.sidebar',
            children: [
                {
                    type: 'item',
                    id: 'general-settings',
                    label: 'settings.general.title',
                    href: '/settings/general',
                    ability: 'manage_settings',
                },
            ],
        },
    ];
}

function translate(key: string): string {
    const dictionary: Record<string, string> = {
        'navigation.dashboard': 'Dashboard',
        'settings.sidebar': 'Settings',
        'settings.general.title': 'General',
    };

    return dictionary[key] ?? key;
}

test('resolves labels via the translate function in input order', () => {
    const definitions = buildDefinitions();

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: () => false,
    });

    assert.equal(resolved.length, 2);
    assert.equal(resolved[0]?.id, 'dashboard');
    assert.equal(resolved[0]?.label, 'Dashboard');
    assert.equal(resolved[1]?.id, 'settings');
    assert.equal(resolved[1]?.label, 'Settings');

    if (resolved[1]?.type !== 'group') {
        throw new Error('expected settings node to be a group');
    }

    assert.equal(resolved[1].children.length, 1);
    assert.equal(resolved[1].children[0]?.label, 'General');
});

test('keeps a leaf visible when the required ability is granted', () => {
    const definitions = buildDefinitions();

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: () => false,
    });

    const settingsGroup = resolved[1];
    if (settingsGroup?.type !== 'group') {
        throw new Error('expected settings node to be a group');
    }

    assert.equal(settingsGroup.children.length, 1);
    assert.equal(settingsGroup.children[0]?.id, 'general-settings');
});

test('filters out a leaf and removes the resulting empty group when the ability is missing', () => {
    const definitions = buildDefinitions();

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: false },
        translate,
        isCurrentOrParentUrl: () => false,
    });

    assert.equal(resolved.length, 1);
    assert.equal(resolved[0]?.id, 'dashboard');
    assert.equal(
        resolved.some((node) => node.id === 'settings'),
        false,
    );
});

test('marks a leaf active when isCurrentOrParentUrl matches its href', () => {
    const definitions = buildDefinitions();

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: (href) => href === '/dashboard',
    });

    assert.equal(resolved[0]?.isActive, true);
});

test('marks a parent group active when any child is active', () => {
    const definitions = buildDefinitions();

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: (href) => href === '/settings/general',
    });

    const settingsGroup = resolved[1];
    if (settingsGroup?.type !== 'group') {
        throw new Error('expected settings node to be a group');
    }

    assert.equal(settingsGroup.isActive, true);
    assert.equal(settingsGroup.children[0]?.isActive, true);
});

test('preserves external link metadata on resolved leaves', () => {
    const definitions: NavigationDefinition[] = [
        {
            type: 'item',
            id: 'docs',
            label: 'navigation.dashboard',
            href: 'https://example.com/docs',
            isExternal: true,
        },
    ];

    const resolved = resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: () => false,
    });

    if (resolved[0]?.type !== 'item') {
        throw new Error('expected docs node to be a leaf item');
    }

    assert.equal(resolved[0].isExternal, true);
});

test('does not mutate the input definitions', () => {
    const definitions = buildDefinitions();
    const snapshot = JSON.stringify(definitions);

    resolveNavigation(definitions, {
        abilities: { manage_settings: true },
        translate,
        isCurrentOrParentUrl: () => true,
    });

    assert.equal(JSON.stringify(definitions), snapshot);
});

test('canonical navigation definition declares the stable ids in order with correct metadata', async () => {
    const source = await readFile(appNavigationPath, 'utf8');

    // primary: dashboard before settings
    const dashboardIndex = source.indexOf("id: 'dashboard'");
    const settingsIndex = source.indexOf("id: 'settings'");
    assert.notEqual(dashboardIndex, -1, 'expected a dashboard entry');
    assert.notEqual(settingsIndex, -1, 'expected a settings entry');
    assert.ok(
        dashboardIndex < settingsIndex,
        'expected dashboard to precede settings in the primary definition',
    );

    // secondary: repository before documentation
    const repositoryIndex = source.indexOf("id: 'repository'");
    const documentationIndex = source.indexOf("id: 'documentation'");
    assert.notEqual(repositoryIndex, -1, 'expected a repository entry');
    assert.notEqual(documentationIndex, -1, 'expected a documentation entry');
    assert.ok(
        repositoryIndex < documentationIndex,
        'expected repository to precede documentation in the secondary definition',
    );

    // Settings declares the manage_settings ability
    const settingsBlock = source.slice(settingsIndex, repositoryIndex);
    assert.match(settingsBlock, /ability:\s*'manage_settings'/);

    // internal items use Wayfinder helpers, not hardcoded URLs
    assert.match(source, /href:\s*dashboard\(\)/);
    assert.match(source, /href:\s*settingsIndex\(\)/);
    assert.doesNotMatch(source, /['"]\/dashboard['"]/);
    assert.doesNotMatch(source, /['"]\/settings['"]/);

    // secondary items explicitly declare external behavior and carry external URLs
    const secondaryBlock = source.slice(repositoryIndex);
    const repositoryEntry = secondaryBlock.slice(
        0,
        secondaryBlock.indexOf("id: 'documentation'"),
    );
    const documentationEntry = secondaryBlock.slice(
        secondaryBlock.indexOf("id: 'documentation'"),
    );

    assert.match(repositoryEntry, /isExternal:\s*true/);
    assert.match(
        repositoryEntry,
        /href:\s*'https:\/\/github\.com\/laravel\/vue-starter-kit'/,
    );
    assert.match(documentationEntry, /isExternal:\s*true/);
    assert.match(
        documentationEntry,
        /href:\s*'https:\/\/laravel\.com\/docs\/starter-kits#vue'/,
    );
});

test('AppSidebar renders from the centralized navigation composable, not local menu arrays', async () => {
    const appSidebarPath = new URL(
        '../../resources/js/components/AppSidebar.vue',
        import.meta.url,
    );
    const source = await readFile(appSidebarPath, 'utf8');

    // Consumes the canonical navigation composable.
    assert.match(
        source,
        /import\s*\{[^}]*useAppNavigation[^}]*\}\s*from\s*'@\/composables\/useAppNavigation'/,
    );
    assert.match(source, /useAppNavigation\(\)/);

    // Does not construct local menu arrays.
    assert.doesNotMatch(source, /mainNavItems/);
    assert.doesNotMatch(source, /footerNavItems/);

    // Does not import the settings route helper for menu building.
    assert.doesNotMatch(source, /settingsIndex/);
    assert.doesNotMatch(source, /from\s*'@\/routes\/settings'/);

    // Does not import menu icons directly (icons now flow through resolved nodes).
    assert.doesNotMatch(source, /LayoutGrid/);
    assert.doesNotMatch(source, /\bSettings\b/);
    assert.doesNotMatch(source, /FolderGit2/);
    assert.doesNotMatch(source, /BookOpen/);

    // Does not read the page directly (abilities/translation now resolved inside the composable).
    assert.doesNotMatch(source, /usePage/);
    assert.doesNotMatch(source, /useTrans/);
});
