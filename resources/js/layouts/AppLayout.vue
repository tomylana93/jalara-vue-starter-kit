<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useStyleSettings } from '@/composables/useStyleSettings';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const layoutComponent = computed(() =>
    page.props.style.site_layout === 'header'
        ? AppHeaderLayout
        : AppSidebarLayout,
);

useStyleSettings();
</script>

<template>
    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
