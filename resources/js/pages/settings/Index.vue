<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Palette, Wrench } from '@lucide/vue';
import { computed } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { useTrans } from '@/composables/useTrans';
import { index as settingsIndex } from '@/routes/settings';
import { edit as generalEdit } from '@/routes/settings/general';
import { edit as styleEdit } from '@/routes/settings/style';
import type { CardItem } from '@/types';

const { trans } = useTrans();

defineOptions({
    inheritAttrs: false,
    layout: {
        breadcrumbs: [
            {
                title: 'settings.index.title',
                href: settingsIndex(),
            },
        ],
    },
});

const settingsCards = computed<CardItem[]>(() => [
    {
        title: trans('settings.general.title'),
        description: trans('settings.general.description'),
        href: generalEdit(),
        icon: Wrench,
    },
    {
        title: trans('style.title'),
        description: trans('style.description'),
        href: styleEdit(),
        icon: Palette,
    },
]);
</script>

<template>
    <Head :title="trans('settings.index.title')" />

    <PageWrapper
        :title="trans('settings.index.heading')"
        :description="trans('settings.index.description')"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="item in settingsCards"
                :key="item.title"
                class="flex h-full flex-col"
            >
                <CardContent class="flex flex-1 items-start gap-4">
                    <component :is="item.icon" class="mt-0.5 size-6" />
                    <div class="flex flex-col gap-1">
                        <h3 class="text-sm font-medium">{{ item.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ item.description }}
                        </p>
                    </div>
                </CardContent>
                <CardFooter class="pt-0">
                    <Button :as="Link" :href="item.href">
                        {{ trans('settings.index.action.open') }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </PageWrapper>
</template>
