import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveNavigation } from '../../resources/js/lib/navigation.ts';
import type { NavigationDefinition } from '../../resources/js/types/navigation.ts';

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
