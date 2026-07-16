<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Props = {
    imageClass?: string;
    nameClass?: string;
    showName?: boolean;
};

withDefaults(defineProps<Props>(), {
    imageClass: 'h-8 w-auto',
    nameClass: 'text-sm font-semibold',
    showName: true,
});

const page = usePage();
const usesLogo = computed(() => page.props.style.site_logo_style === 'logo');
const lightSource = computed(() =>
    usesLogo.value ? page.props.branding.logo : page.props.branding.icon,
);
const darkSource = computed(() =>
    usesLogo.value
        ? page.props.branding.logo_dark
        : page.props.branding.icon_dark,
);
</script>

<template>
    <span class="flex min-w-0 items-center gap-2">
        <img
            :src="lightSource"
            :alt="page.props.name"
            :class="imageClass"
            class="object-contain dark:hidden"
        />
        <img
            :src="darkSource"
            :alt="page.props.name"
            :class="imageClass"
            class="hidden object-contain dark:block"
        />
        <span v-if="showName && !usesLogo" :class="nameClass" class="truncate">
            {{ page.props.name }}
        </span>
    </span>
</template>
