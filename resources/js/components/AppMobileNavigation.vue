<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { DeepReadonly } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { toUrl } from '@/lib/utils';
import type { NavigationNode } from '@/types/navigation';

defineProps<{
    items: DeepReadonly<NavigationNode[]>;
}>();

const emit = defineEmits<{
    select: [];
}>();

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';
const linkClass =
    'flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent';

function handleSelect(): void {
    emit('select');
}
</script>

<template>
    <nav class="-mx-3 space-y-1">
        <template v-for="node in items" :key="node.id">
            <Link
                v-if="node.type === 'item' && !node.isExternal"
                :href="node.href"
                :class="[linkClass, node.isActive && activeItemStyles]"
                @click="handleSelect"
            >
                <component v-if="node.icon" :is="node.icon" class="h-5 w-5" />
                {{ node.label }}
            </Link>
            <a
                v-else-if="node.type === 'item'"
                :href="toUrl(node.href)"
                target="_blank"
                rel="noopener noreferrer"
                :class="[linkClass, node.isActive && activeItemStyles]"
                @click="handleSelect"
            >
                <component v-if="node.icon" :is="node.icon" class="h-5 w-5" />
                {{ node.label }}
            </a>

            <Collapsible
                v-else
                :default-open="node.isActive"
                class="group/collapsible"
            >
                <CollapsibleTrigger
                    :class="[
                        linkClass,
                        'w-full cursor-pointer justify-between',
                        node.isActive && activeItemStyles,
                    ]"
                >
                    <span class="flex items-center gap-x-3">
                        <component
                            v-if="node.icon"
                            :is="node.icon"
                            class="h-5 w-5"
                        />
                        {{ node.label }}
                    </span>
                    <ChevronRight
                        class="h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                    />
                </CollapsibleTrigger>
                <CollapsibleContent class="space-y-1 pl-6">
                    <template v-for="child in node.children" :key="child.id">
                        <Link
                            v-if="!child.isExternal"
                            :href="child.href"
                            :class="[
                                linkClass,
                                child.isActive && activeItemStyles,
                            ]"
                            @click="handleSelect"
                        >
                            <component
                                v-if="child.icon"
                                :is="child.icon"
                                class="h-5 w-5"
                            />
                            {{ child.label }}
                        </Link>
                        <a
                            v-else
                            :href="toUrl(child.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                            :class="[
                                linkClass,
                                child.isActive && activeItemStyles,
                            ]"
                            @click="handleSelect"
                        >
                            <component
                                v-if="child.icon"
                                :is="child.icon"
                                class="h-5 w-5"
                            />
                            {{ child.label }}
                        </a>
                    </template>
                </CollapsibleContent>
            </Collapsible>
        </template>
    </nav>
</template>
