import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, readonly } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTrans } from '@/composables/useTrans';
import { resolveNavigation } from '@/lib/navigation';
import { primary, secondary } from '@/navigation/app-navigation';
import type { NavigationNode } from '@/types/navigation';

export type UseAppNavigationReturn = {
    primary: DeepReadonly<ComputedRef<NavigationNode[]>>;
    secondary: DeepReadonly<ComputedRef<NavigationNode[]>>;
};

/**
 * Reactive adapter over the canonical navigation definition: resolves
 * `primary`/`secondary` against the current user's abilities, translations,
 * and active URL. Sidebar and header components consume this instead of
 * declaring their own navigation.
 */
export function useAppNavigation(): UseAppNavigationReturn {
    const page = usePage();
    const { trans } = useTrans();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const resolvedPrimary = computed<NavigationNode[]>(() =>
        resolveNavigation(primary, {
            abilities: page.props.auth.abilities,
            translate: trans,
            isCurrentOrParentUrl,
        }),
    );

    const resolvedSecondary = computed<NavigationNode[]>(() =>
        resolveNavigation(secondary, {
            abilities: page.props.auth.abilities,
            translate: trans,
            isCurrentOrParentUrl,
        }),
    );

    return {
        primary: readonly(resolvedPrimary),
        secondary: readonly(resolvedSecondary),
    };
}
