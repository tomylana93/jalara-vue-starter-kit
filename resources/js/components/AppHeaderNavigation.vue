<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { DeepReadonly } from 'vue';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { toUrl } from '@/lib/utils';
import type { NavigationNode } from '@/types/navigation';

defineProps<{
    items: DeepReadonly<NavigationNode[]>;
}>();

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';
</script>

<template>
    <NavigationMenu class="ml-10 flex h-full items-stretch">
        <NavigationMenuList class="flex h-full items-stretch space-x-2">
            <NavigationMenuItem
                v-for="node in items"
                :key="node.id"
                class="relative flex h-full items-center"
            >
                <template v-if="node.type === 'item'">
                    <NavigationMenuLink
                        as-child
                        :active="node.isActive"
                        :class="[
                            navigationMenuTriggerStyle(),
                            node.isActive && activeItemStyles,
                            'h-9 cursor-pointer px-3',
                        ]"
                    >
                        <Link v-if="!node.isExternal" :href="node.href">
                            <component
                                v-if="node.icon"
                                :is="node.icon"
                                class="mr-2 h-4 w-4"
                            />
                            {{ node.label }}
                        </Link>
                        <a
                            v-else
                            :href="toUrl(node.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component
                                v-if="node.icon"
                                :is="node.icon"
                                class="mr-2 h-4 w-4"
                            />
                            {{ node.label }}
                        </a>
                    </NavigationMenuLink>
                    <div
                        v-if="node.isActive"
                        class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                    ></div>
                </template>

                <template v-else>
                    <NavigationMenuTrigger
                        :class="node.isActive && activeItemStyles"
                    >
                        <component
                            v-if="node.icon"
                            :is="node.icon"
                            class="mr-2 h-4 w-4"
                        />
                        {{ node.label }}
                    </NavigationMenuTrigger>
                    <NavigationMenuContent>
                        <ul class="grid w-[220px] gap-1 p-1">
                            <li v-for="child in node.children" :key="child.id">
                                <NavigationMenuLink
                                    as-child
                                    :active="child.isActive"
                                >
                                    <Link
                                        v-if="!child.isExternal"
                                        :href="child.href"
                                    >
                                        <component
                                            v-if="child.icon"
                                            :is="child.icon"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ child.label }}
                                    </Link>
                                    <a
                                        v-else
                                        :href="toUrl(child.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <component
                                            v-if="child.icon"
                                            :is="child.icon"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ child.label }}
                                    </a>
                                </NavigationMenuLink>
                            </li>
                        </ul>
                    </NavigationMenuContent>
                </template>
            </NavigationMenuItem>
        </NavigationMenuList>
    </NavigationMenu>
</template>
