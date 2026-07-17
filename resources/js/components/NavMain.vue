<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { DeepReadonly } from 'vue';
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTrans } from '@/composables/useTrans';
import { toUrl } from '@/lib/utils';
import type { NavigationNode } from '@/types/navigation';

defineProps<{
    items: DeepReadonly<NavigationNode[]>;
}>();

const { state, isMobile, setOpenMobile } = useSidebar();
const { trans } = useTrans();

/**
 * Tracks which collapsed-icon group's dropdown is currently open so its
 * hover tooltip can be suppressed while the dropdown is showing.
 */
const openGroupDropdownId = ref<string | null>(null);

function handleChildSelected(): void {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}

function onGroupDropdownOpenChange(nodeId: string, open: boolean): void {
    openGroupDropdownId.value = open ? nodeId : null;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{
            trans('navigation.platform.label')
        }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="node in items" :key="node.id">
                <SidebarMenuItem v-if="node.type === 'item'">
                    <SidebarMenuButton
                        as-child
                        :is-active="node.isActive"
                        :tooltip="node.label"
                    >
                        <Link
                            v-if="!node.isExternal"
                            :href="node.href"
                            @click="handleChildSelected"
                        >
                            <component :is="node.icon" v-if="node.icon" />
                            <span>{{ node.label }}</span>
                        </Link>
                        <a
                            v-else
                            :href="toUrl(node.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                            @click="handleChildSelected"
                        >
                            <component :is="node.icon" v-if="node.icon" />
                            <span>{{ node.label }}</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem v-else>
                    <template v-if="state === 'collapsed' && !isMobile">
                        <Tooltip
                            :open="
                                openGroupDropdownId === node.id
                                    ? false
                                    : undefined
                            "
                        >
                            <DropdownMenu
                                @update:open="
                                    (open) =>
                                        onGroupDropdownOpenChange(node.id, open)
                                "
                            >
                                <TooltipTrigger as-child>
                                    <DropdownMenuTrigger as-child>
                                        <SidebarMenuButton
                                            :is-active="node.isActive"
                                            :aria-label="node.label"
                                        >
                                            <component
                                                :is="node.icon"
                                                v-if="node.icon"
                                            />
                                            <span>{{ node.label }}</span>
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                </TooltipTrigger>
                                <DropdownMenuContent side="right" align="start">
                                    <DropdownMenuItem
                                        v-for="child in node.children"
                                        :key="child.id"
                                        as-child
                                        :data-active="child.isActive"
                                    >
                                        <Link
                                            v-if="!child.isExternal"
                                            :href="child.href"
                                            @click="handleChildSelected"
                                        >
                                            <span>{{ child.label }}</span>
                                        </Link>
                                        <a
                                            v-else
                                            :href="toUrl(child.href)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            @click="handleChildSelected"
                                        >
                                            <span>{{ child.label }}</span>
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <TooltipContent side="right" align="center">
                                {{ node.label }}
                            </TooltipContent>
                        </Tooltip>
                    </template>

                    <Collapsible
                        v-else
                        :default-open="node.isActive"
                        class="group/collapsible"
                    >
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="node.isActive"
                                :aria-label="node.label"
                            >
                                <component :is="node.icon" v-if="node.icon" />
                                <span>{{ node.label }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="child in node.children"
                                    :key="child.id"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="child.isActive"
                                    >
                                        <Link
                                            v-if="!child.isExternal"
                                            :href="child.href"
                                            @click="handleChildSelected"
                                        >
                                            <span>{{ child.label }}</span>
                                        </Link>
                                        <a
                                            v-else
                                            :href="toUrl(child.href)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            @click="handleChildSelected"
                                        >
                                            <span>{{ child.label }}</span>
                                        </a>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
