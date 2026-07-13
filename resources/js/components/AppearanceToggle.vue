<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { appearance, updateAppearance } = useAppearance();

const appearances = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const;

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
            <Button variant="ghost" size="icon" aria-label="Change appearance">
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
