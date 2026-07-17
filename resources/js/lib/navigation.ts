import type { Auth } from '@/types/auth';
import type {
    NavigationDefinition,
    NavigationGroupDefinition,
    NavigationGroupNode,
    NavigationHref,
    NavigationItemDefinition,
    NavigationItemNode,
    NavigationNode,
} from '@/types/navigation';
import type { TranslationKey } from '@/types/translation.generated';

/**
 * Dependencies injected into {@link resolveNavigation} so the resolver
 * itself stays framework-free and pure: no Vue, no Inertia runtime, no DOM.
 */
export type ResolveNavigationOptions = {
    abilities: Auth['abilities'];
    translate: (key: TranslationKey) => string;
    isCurrentOrParentUrl: (href: NavigationHref) => boolean;
};

function isLeafVisible(
    leaf: NavigationItemDefinition,
    abilities: Auth['abilities'],
): boolean {
    return leaf.ability === undefined || abilities[leaf.ability];
}

function resolveLeaf(
    leaf: NavigationItemDefinition,
    options: ResolveNavigationOptions,
): NavigationItemNode {
    return {
        type: 'item',
        id: leaf.id,
        label: options.translate(leaf.label),
        href: leaf.href,
        icon: leaf.icon,
        isExternal: leaf.isExternal,
        ability: leaf.ability,
        isActive: options.isCurrentOrParentUrl(leaf.href),
    };
}

function resolveGroup(
    group: NavigationGroupDefinition,
    options: ResolveNavigationOptions,
): NavigationGroupNode | null {
    const children = group.children
        .filter((child) => isLeafVisible(child, options.abilities))
        .map((child) => resolveLeaf(child, options));

    if (children.length === 0) {
        return null;
    }

    return {
        type: 'group',
        id: group.id,
        label: options.translate(group.label),
        icon: group.icon,
        children,
        isActive: children.some((child) => child.isActive),
    };
}

/**
 * Resolves a definition tree into renderable navigation nodes: translates
 * labels, filters leaves the current abilities don't permit, drops groups
 * left empty by that filtering, and derives active state — leaves from
 * `isCurrentOrParentUrl`, groups from whether any child is active.
 *
 * Pure: produces new objects and never mutates `definitions`.
 */
export function resolveNavigation(
    definitions: readonly NavigationDefinition[],
    options: ResolveNavigationOptions,
): NavigationNode[] {
    const nodes: NavigationNode[] = [];

    for (const definition of definitions) {
        if (definition.type === 'item') {
            if (isLeafVisible(definition, options.abilities)) {
                nodes.push(resolveLeaf(definition, options));
            }

            continue;
        }

        const group = resolveGroup(definition, options);

        if (group !== null) {
            nodes.push(group);
        }
    }

    return nodes;
}
