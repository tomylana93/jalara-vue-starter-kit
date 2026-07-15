<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { update } from '@/actions/App/Http/Controllers/Settings/GeneralSettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { index as settingsIndex } from '@/routes/settings';
import { edit as generalEdit } from '@/routes/settings/general';
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
                title: 'Settings',
                href: settingsIndex(),
            },
            {
                title: 'General settings',
                href: generalEdit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="trans('settings.general.title')" />

    <PageWrapper
        :title="trans('settings.general.heading')"
        :description="trans('settings.general.description')"
    >
        <Form
            v-bind="update.form()"
            class="flex flex-col gap-6"
            #default="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{
                        trans('settings.general.heading')
                    }}</CardTitle>
                    <CardDescription>{{
                        trans('settings.general.description')
                    }}</CardDescription>
                </CardHeader>

                <CardContent class="flex flex-col gap-6">
                    <div class="grid gap-2">
                        <Label for="site_name">{{
                            trans('settings.general.label.site_name')
                        }}</Label>
                        <Input
                            id="site_name"
                            name="site_name"
                            :default-value="props.generalSettings.site_name"
                            class="block w-full"
                            :placeholder="
                                trans('settings.general.placeholder.site_name')
                            "
                        />
                        <InputError :message="errors.site_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="site_description">{{
                            trans('settings.general.label.site_description')
                        }}</Label>
                        <Textarea
                            id="site_description"
                            name="site_description"
                            :default-value="
                                props.generalSettings.site_description
                            "
                            class="block w-full"
                            :placeholder="
                                trans(
                                    'settings.general.placeholder.site_description',
                                )
                            "
                        />
                        <InputError :message="errors.site_description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="site_locale">{{
                            trans('settings.general.label.site_locale')
                        }}</Label>
                        <Select
                            name="site_locale"
                            :default-value="props.generalSettings.site_locale"
                        >
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
                        <InputError :message="errors.site_locale" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-general-settings-button"
                        >
                            {{ trans('settings.general.action.save') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </Form>
    </PageWrapper>
</template>
