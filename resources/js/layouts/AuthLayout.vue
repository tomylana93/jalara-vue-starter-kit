<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

import type { TranslationKey } from '@/types/translation.generated';

const { title, description } = defineProps<{
    title?: TranslationKey;
    description?: TranslationKey;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
