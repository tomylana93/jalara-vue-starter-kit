<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTrans } from '@/composables/useTrans';
import { toUrl } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { trans } = useTrans();
const { isCurrentOrParentUrl } = useCurrentUrl();

const navigationItems = computed<NavItem[]>(() => [
    {
        title: trans('settings.nav.profile'),
        href: editProfile(),
    },
    {
        title: trans('settings.nav.security'),
        href: editSecurity(),
    },
]);
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row lg:gap-12">
        <aside class="w-full lg:w-48 lg:shrink-0">
            <nav
                class="flex flex-col gap-1"
                :aria-label="trans('settings.layout.aria')"
            >
                <Button
                    v-for="item in navigationItems"
                    :key="toUrl(item.href)"
                    variant="ghost"
                    :class="[
                        'w-full justify-start',
                        { 'bg-muted': isCurrentOrParentUrl(item.href) },
                    ]"
                    as-child
                >
                    <Link
                        :href="item.href"
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                    >
                        {{ item.title }}
                    </Link>
                </Button>
            </nav>
        </aside>

        <Separator class="lg:hidden" />

        <div class="w-full min-w-0 flex-1">
            <section class="space-y-12">
                <slot />
            </section>
        </div>
    </div>
</template>
