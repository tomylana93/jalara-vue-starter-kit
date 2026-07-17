import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import type { Auth } from '@/types/auth';
import type { TranslationKey } from '@/types/translation.generated';

export type BreadcrumbItem = {
    title: TranslationKey;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type CardItem = {
    title: string;
    description: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
};

/**
 * The href type accepted by navigation leaves, matching the shape already
 * used by Inertia's `<Link>` component.
 */
export type NavigationHref = NonNullable<InertiaLinkProps['href']>;

/**
 * The ability keys that can gate visibility of a navigation leaf.
 */
export type NavigationAbility = keyof Auth['abilities'];

/**
 * A single, non-nested navigation leaf definition. Leaves own `href` and
 * explicit external-link metadata; they must not carry `children`.
 */
export type NavigationItemDefinition = {
    type: 'item';
    id: string;
    label: TranslationKey;
    href: NavigationHref;
    icon?: LucideIcon;
    isExternal?: boolean;
    ability?: NavigationAbility;
};

/**
 * A navigation group definition. Groups own `children` (leaves only — a
 * single level of nesting) and must not carry `href`.
 */
export type NavigationGroupDefinition = {
    type: 'group';
    id: string;
    label: TranslationKey;
    icon?: LucideIcon;
    children: NavigationItemDefinition[];
};

/**
 * The discriminated union of navigation node definitions, keyed on `type`.
 */
export type NavigationDefinition = NavigationItemDefinition | NavigationGroupDefinition;

/**
 * A resolved navigation leaf: the definition with its label translated and
 * its active state derived.
 */
export type NavigationItemNode = Omit<NavigationItemDefinition, 'label'> & {
    label: string;
    isActive: boolean;
};

/**
 * A resolved navigation group: the definition with its label translated,
 * its children resolved, and its active state derived from its children.
 */
export type NavigationGroupNode = Omit<NavigationGroupDefinition, 'label' | 'children'> & {
    label: string;
    isActive: boolean;
    children: NavigationItemNode[];
};

/**
 * The discriminated union of resolved navigation nodes, keyed on `type`.
 */
export type NavigationNode = NavigationItemNode | NavigationGroupNode;
