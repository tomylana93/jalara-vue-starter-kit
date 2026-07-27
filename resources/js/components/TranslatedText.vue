<script setup lang="ts">
import { computed } from 'vue';

import { useTrans } from '@/composables/useTrans';
import { translationParts } from '@/lib/translation';

import type { Replacements } from '@/lib/translation';
import type { TranslationKey } from '@/types/translation.generated';

const props = withDefaults(
    defineProps<{
        translationKey: TranslationKey;
        replacements?: Replacements;
        slotNames?: readonly string[];
    }>(),
    {
        replacements: () => ({}),
        slotNames: () => [],
    },
);

const { trans } = useTrans();

const parts = computed(() =>
    translationParts(
        trans(props.translationKey, props.replacements),
        props.slotNames,
    ),
);
</script>

<template>
    <template v-for="(part, index) in parts" :key="index">
        <slot v-if="part.slot" :name="part.slot" />
        <template v-else>{{ part.text }}</template>
    </template>
</template>
