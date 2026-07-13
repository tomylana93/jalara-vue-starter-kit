<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { update } from '@/routes/settings/general';
import type { GeneralSettings, SelectOption } from '@/types';

type Props = {
    generalSettings: GeneralSettings;
    localeOptions: SelectOption[];
};

const props = defineProps<Props>();

const { trans } = useTrans();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'General settings',
                href: update.url(),
            },
        ],
    },
});

type GeneralSettingsFormData = {
    site_name: string;
    site_description: string;
    site_locale: string;
};

const form = useForm<GeneralSettingsFormData>({
    site_name: props.generalSettings.site_name,
    site_description: props.generalSettings.site_description,
    site_locale: props.generalSettings.site_locale,
});

function submit(): void {
    form.submit(update(), { preserveScroll: true });
}
</script>

<template>
    <Head :title="trans('settings.general.title')" />

    <h1 class="sr-only">{{ trans('settings.general.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.general.heading')"
            :description="trans('settings.general.description')"
        />

        <Card class="max-w-2xl">
            <CardContent>
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="site_name">{{
                            trans('settings.general.label.site_name')
                        }}</Label>
                        <Input
                            id="site_name"
                            v-model="form.site_name"
                            class="mt-1 block w-full"
                            required
                            :placeholder="
                                trans('settings.general.placeholder.site_name')
                            "
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.site_name"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="site_description">{{
                            trans('settings.general.label.site_description')
                        }}</Label>
                        <Textarea
                            id="site_description"
                            v-model="form.site_description"
                            class="mt-1 block w-full"
                            :placeholder="
                                trans(
                                    'settings.general.placeholder.site_description',
                                )
                            "
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.site_description"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="site_locale">{{
                            trans('settings.general.label.site_locale')
                        }}</Label>
                        <Select v-model="form.site_locale">
                            <SelectTrigger id="site_locale" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in props.localeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.site_locale"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="form.processing"
                            data-test="update-general-settings-button"
                        >
                            {{ trans('settings.general.action.save') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
