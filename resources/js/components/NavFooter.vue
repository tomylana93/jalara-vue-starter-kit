<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { DeepReadonly } from 'vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import type { NavigationNode } from '@/types/navigation';

type Props = {
    items: DeepReadonly<NavigationNode[]>;
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="node in items" :key="node.id">
                    <SidebarMenuButton
                        v-if="node.type === 'item'"
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                    >
                        <Link v-if="!node.isExternal" :href="node.href">
                            <component :is="node.icon" v-if="node.icon" />
                            <span>{{ node.label }}</span>
                        </Link>
                        <a
                            v-else
                            :href="toUrl(node.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="node.icon" v-if="node.icon" />
                            <span>{{ node.label }}</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
