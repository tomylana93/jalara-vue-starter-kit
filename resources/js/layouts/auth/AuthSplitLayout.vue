<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppBrand from '@/components/AppBrand.vue';
import { useTrans } from '@/composables/useTrans';
import { home } from '@/routes';

import type { TranslationKey } from '@/types/translation.generated';

const page = usePage();

defineProps<{
    title?: TranslationKey;
    description?: TranslationKey;
}>();

const { trans } = useTrans();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <div
                v-if="page.props.branding.auth_split_background"
                class="absolute inset-0 bg-cover bg-center"
                :style="{
                    backgroundImage: `url(${page.props.branding.auth_split_background})`,
                }"
            />
            <div class="absolute inset-0 bg-zinc-900/65" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppBrand
                    image-class="h-10 w-auto"
                    name-class="text-lg font-semibold text-white"
                />
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ trans(title) }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ trans(description) }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
