<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { useTrans } from '@/composables/useTrans';

import type { Appearance } from '@/types';

const { appearance, updateAppearance } = useAppearance();
const { trans } = useTrans();

const appearances = computed(() => [
    {
        value: 'light' as const,
        label: trans('general.appearance.light'),
        icon: Sun,
    },
    {
        value: 'dark' as const,
        label: trans('general.appearance.dark'),
        icon: Moon,
    },
    {
        value: 'system' as const,
        label: trans('general.appearance.system'),
        icon: Monitor,
    },
]);

function selectAppearance(value: Appearance): void {
    updateAppearance(value);
}

function onUpdateModelValue(value: unknown): void {
    if (value === 'light' || value === 'dark' || value === 'system') {
        selectAppearance(value);
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                :aria-label="trans('general.appearance.change')"
            >
                <Sun v-if="appearance === 'light'" class="size-5" />
                <Moon v-else-if="appearance === 'dark'" class="size-5" />
                <Monitor v-else class="size-5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="onUpdateModelValue"
            >
                <DropdownMenuRadioItem
                    v-for="item in appearances"
                    :key="item.value"
                    :value="item.value"
                >
                    <component :is="item.icon" class="mr-2 size-4" />{{
                        item.label
                    }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
