<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useStyleSettings } from '@/composables/useStyleSettings';
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import type { TranslationKey } from '@/types/translation.generated';

const { title, description } = defineProps<{
    title?: TranslationKey;
    description?: TranslationKey;
}>();

const page = usePage();
const layoutComponent = computed(() => {
    if (page.props.style.site_auth_layout === 'card') {
        return AuthCardLayout;
    }

    if (page.props.style.site_auth_layout === 'split') {
        return AuthSplitLayout;
    }

    return AuthSimpleLayout;
});

useStyleSettings();
</script>

<template>
    <component :is="layoutComponent" :title="title" :description="description">
        <slot />
    </component>
</template>
