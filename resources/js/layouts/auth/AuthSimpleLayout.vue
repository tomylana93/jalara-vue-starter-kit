<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AppBrand from '@/components/AppBrand.vue';
import { useTrans } from '@/composables/useTrans';
import { home } from '@/routes';

import type { TranslationKey } from '@/types/translation.generated';

defineProps<{
    title?: TranslationKey;
    description?: TranslationKey;
}>();

const { trans } = useTrans();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <AppBrand image-class="h-9 w-auto" :show-name="false" />
                        <span class="sr-only">{{
                            title ? trans(title) : ''
                        }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">
                            {{ title ? trans(title) : '' }}
                        </h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description ? trans(description) : '' }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
