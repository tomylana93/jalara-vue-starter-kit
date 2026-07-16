<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import {
    destroy as destroyBrandingUpload,
    store as storeBrandingUpload,
} from '@/actions/App/Http/Controllers/Settings/BrandingUploadController';
import { update } from '@/actions/App/Http/Controllers/Settings/StyleSettingsController';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Uploader } from '@/components/uploader';
import type { UploaderExistingFile } from '@/components/uploader';
import { useTrans } from '@/composables/useTrans';
import { index as settingsIndex } from '@/routes/settings';
import { edit as styleEdit } from '@/routes/settings/style';
import type { BrandingAssets, SelectOption, StyleSettings } from '@/types';

type Props = {
    styleSettings: StyleSettings;
    logoStyleOptions: SelectOption[];
    authLayoutOptions: SelectOption[];
    layoutOptions: SelectOption[];
    themeOptions: SelectOption[];
    fontOptions: SelectOption[];
    branding: BrandingAssets;
    existingFiles: Record<AssetField, UploaderExistingFile[]>;
};

type AssetField =
    | 'icon'
    | 'icon_dark'
    | 'logo'
    | 'logo_dark'
    | 'favicon'
    | 'auth_split_background';

defineProps<Props>();
defineOptions({
    inheritAttrs: false,
    layout: {
        breadcrumbs: [
            {
                title: 'settings.index.title',
                href: settingsIndex(),
            },
            {
                title: 'style.title',
                href: styleEdit(),
            },
        ],
    },
});

const { trans } = useTrans();

const assetFields: Array<{
    key: AssetField;
    accept: string[];
    maxSize: number;
}> = [
    {
        key: 'icon',
        accept: ['image/png', 'image/jpeg', 'image/webp'],
        maxSize: 2 * 1024 * 1024,
    },
    {
        key: 'icon_dark',
        accept: ['image/png', 'image/jpeg', 'image/webp'],
        maxSize: 2 * 1024 * 1024,
    },
    {
        key: 'logo',
        accept: ['image/png', 'image/jpeg', 'image/webp'],
        maxSize: 2 * 1024 * 1024,
    },
    {
        key: 'logo_dark',
        accept: ['image/png', 'image/jpeg', 'image/webp'],
        maxSize: 2 * 1024 * 1024,
    },
    {
        key: 'favicon',
        accept: ['image/png', 'image/webp', 'image/x-icon'],
        maxSize: 1024 * 1024,
    },
    {
        key: 'auth_split_background',
        accept: ['image/jpeg', 'image/webp'],
        maxSize: 5 * 1024 * 1024,
    },
];

const uploadIds = ref<Record<AssetField, string[]>>({
    icon: [],
    icon_dark: [],
    logo: [],
    logo_dark: [],
    favicon: [],
    auth_split_background: [],
});
const removedIds = ref<Record<AssetField, Array<string | number>>>({
    icon: [],
    icon_dark: [],
    logo: [],
    logo_dark: [],
    favicon: [],
    auth_split_background: [],
});
</script>

<template>
    <PageWrapper
        :title="trans('style.title')"
        :description="trans('style.description')"
    >
        <Head :title="trans('style.title')" />

        <Form
            v-bind="update.form()"
            class="grid gap-6"
            :validation-timeout="750"
            #default="{ errors, invalid, validate, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ trans('style.title') }}</CardTitle>
                    <CardDescription>{{
                        trans('style.description')
                    }}</CardDescription>
                </CardHeader>

                <CardContent class="flex flex-col gap-6">
                    <div
                        v-for="field in [
                            ['site_logo_style', logoStyleOptions],
                            ['site_auth_layout', authLayoutOptions],
                            ['site_layout', layoutOptions],
                            ['site_theme', themeOptions],
                            ['site_font', fontOptions],
                        ] as const"
                        :key="field[0]"
                        class="grid gap-2"
                    >
                        <Label :for="field[0]">{{
                            trans(`style.label.${field[0]}`)
                        }}</Label>
                        <Select
                            :name="field[0]"
                            :default-value="styleSettings[field[0]]"
                        >
                            <SelectTrigger
                                :id="field[0]"
                                class="w-full"
                                :aria-invalid="invalid(field[0])"
                                @blur="validate(field[0])"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in field[1]"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors[field[0]]" />
                    </div>

                    <div
                        v-for="asset in assetFields"
                        :key="asset.key"
                        class="grid gap-2"
                    >
                        <Label :for="asset.key">
                            {{ trans(`style.asset.${asset.key}`) }}
                        </Label>
                        <input
                            type="hidden"
                            :name="`${asset.key}_upload_id`"
                            :value="uploadIds[asset.key][0] ?? ''"
                        />
                        <input
                            type="hidden"
                            :name="`${asset.key}_remove`"
                            :value="
                                removedIds[asset.key].length > 0 ? '1' : '0'
                            "
                        />
                        <Uploader
                            :id="asset.key"
                            v-model="uploadIds[asset.key]"
                            v-model:removed="removedIds[asset.key]"
                            :upload-url="storeBrandingUpload.url(asset.key)"
                            :delete-url-resolver="
                                (id) => destroyBrandingUpload.url(id)
                            "
                            :existing-files="existingFiles[asset.key]"
                            :accepted-file-types="asset.accept"
                            :max-file-size="asset.maxSize"
                            :multiple="false"
                            :max-files="1"
                        />
                        <InputError
                            :message="errors[`${asset.key}_upload_id`]"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-style-settings-button"
                        >
                            {{ trans('style.action.save') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </Form>
    </PageWrapper>
</template>
