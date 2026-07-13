<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTrans } from '@/composables/useTrans';
import { index as settingsIndex } from '@/routes/settings';
import { edit as generalEdit } from '@/routes/settings/general';

const { trans } = useTrans();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: settingsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="trans('settings.index.title')" />

    <h1 class="sr-only">{{ trans('settings.index.heading') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.index.heading')"
            :description="trans('settings.index.description')"
        />

        <Link :href="generalEdit()" class="block max-w-xl">
            <Card class="transition-colors hover:bg-accent/50">
                <CardHeader>
                    <CardTitle>{{
                        trans('settings.index.general.title')
                    }}</CardTitle>
                    <CardDescription>{{
                        trans('settings.index.general.description')
                    }}</CardDescription>
                </CardHeader>
            </Card>
        </Link>
    </div>
</template>
