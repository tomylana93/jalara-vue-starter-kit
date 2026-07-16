import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
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
