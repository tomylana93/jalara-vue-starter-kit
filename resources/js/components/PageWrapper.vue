<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    class?: HTMLAttributes['class'];
    contentClass?: HTMLAttributes['class'];
    description?: string;
    title?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <section :class="cn('space-y-6 px-4 py-6 md:px-6', props.class)">
        <div
            v-if="title || description || $slots.header || $slots.actions"
            class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
        >
            <slot name="header">
                <div v-if="title || description" class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </slot>

            <div
                v-if="$slots.actions"
                class="flex shrink-0 flex-wrap items-center gap-2"
            >
                <slot name="actions" />
            </div>
        </div>

        <div :class="props.contentClass">
            <slot />
        </div>
    </section>
</template>
