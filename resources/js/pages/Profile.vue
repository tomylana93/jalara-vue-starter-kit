<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    destroy as destroyTemporaryAvatarUpload,
    store as storeAvatarUpload,
} from '@/actions/App/Http/Controllers/Profile/AvatarUploadController';
import ProfileController from '@/actions/App/Http/Controllers/Profile/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Uploader } from '@/components/uploader';
import type { UploaderExistingFile } from '@/components/uploader';
import { useTrans } from '@/composables/useTrans';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    avatar: UploaderExistingFile | null;
};

const props = defineProps<Props>();

const { trans } = useTrans();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'settings.profile.heading',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const temporaryAvatarUploadIds = ref<string[]>([]);
const avatarInitials = computed(() =>
    user.value.name
        .split(' ')
        .map((name) => name[0])
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);
</script>

<template>
    <PageWrapper
        :title="trans('settings.profile.heading')"
        :description="trans('settings.profile.description')"
    >
        <Head :title="trans('settings.profile.title')" />

        <SettingsLayout>
            <div class="flex flex-col gap-6">
                <Heading
                    variant="small"
                    :title="trans('profile.information.heading')"
                    :description="trans('profile.information.description')"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]"
                    :validation-timeout="750"
                    v-slot="{ errors, invalid, validate, processing }"
                >
                    <input
                        type="hidden"
                        name="temporary_avatar_upload_id"
                        :value="temporaryAvatarUploadIds[0] ?? ''"
                    />

                    <div
                        class="flex flex-col gap-4 xl:col-start-2 xl:row-span-5"
                    >
                        <Label class="self-start">{{
                            trans('profile.avatar.label')
                        }}</Label>

                        <div class="flex justify-center">
                            <div class="group relative">
                                <Avatar class="size-40">
                                    <AvatarImage
                                        v-if="props.avatar"
                                        :src="props.avatar.source"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback>{{
                                        avatarInitials
                                    }}</AvatarFallback>
                                </Avatar>

                                <Button
                                    v-if="props.avatar"
                                    variant="ghost"
                                    size="icon"
                                    as-child
                                    class="absolute -top-1 -right-1 size-7 rounded-full bg-background/90 text-destructive opacity-0 shadow-sm transition-opacity group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive focus-visible:opacity-100"
                                >
                                    <Link
                                        :href="
                                            ProfileController.destroyAvatar()
                                        "
                                        method="delete"
                                        as="button"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only">{{
                                            trans('profile.avatar.remove')
                                        }}</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <Uploader
                            id="avatar"
                            v-model="temporaryAvatarUploadIds"
                            :upload-url="storeAvatarUpload.url()"
                            :delete-url-resolver="
                                (id) => destroyTemporaryAvatarUpload.url(id)
                            "
                            :accepted-file-types="['image/jpeg', 'image/webp']"
                            :max-file-size="2 * 1024 * 1024"
                            preview-size="compact"
                            :messages="{
                                invalidType: trans(
                                    'profile.avatar.uploader.invalid_type',
                                ),
                                tooLarge: trans(
                                    'profile.avatar.uploader.too_large',
                                ),
                                uploadFailed: trans(
                                    'profile.avatar.uploader.upload_failed',
                                ),
                                removeFailed: trans(
                                    'profile.avatar.uploader.remove_failed',
                                ),
                            }"
                        />
                        <InputError
                            :message="errors.temporary_avatar_upload_id"
                        />
                    </div>

                    <div class="grid gap-2 xl:col-start-1">
                        <Label for="name">{{
                            trans('profile.form.label.name')
                        }}</Label>
                        <Input
                            id="name"
                            class="block w-full"
                            name="name"
                            :default-value="user.name"
                            autocomplete="name"
                            :placeholder="
                                trans('profile.form.placeholder.name')
                            "
                            :aria-invalid="invalid('name')"
                            @blur="validate('name')"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2 xl:col-start-1">
                        <Label for="email">{{
                            trans('profile.form.label.email')
                        }}</Label>
                        <Input
                            id="email"
                            class="block w-full"
                            name="email"
                            :default-value="user.email"
                            autocomplete="username"
                            :placeholder="
                                trans('profile.form.placeholder.email')
                            "
                            :aria-invalid="invalid('email')"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2 xl:col-start-1">
                        <Label for="phone">{{
                            trans('profile.form.label.phone')
                        }}</Label>
                        <Input
                            id="phone"
                            type="tel"
                            class="block w-full"
                            name="phone"
                            :default-value="user.phone ?? ''"
                            autocomplete="tel"
                            :placeholder="
                                trans('profile.form.placeholder.phone')
                            "
                            :aria-invalid="invalid('phone')"
                            @blur="validate('phone')"
                        />
                        <InputError :message="errors.phone" />
                    </div>

                    <div
                        v-if="
                            page.props.mustVerifyEmail &&
                            !user.email_verified_at
                        "
                        class="xl:col-start-1"
                    >
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ trans('profile.email_verification.unverified') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                {{
                                    trans(
                                        'profile.email_verification.resend_link',
                                    )
                                }}
                            </Link>
                        </p>

                        <div
                            v-if="
                                page.props.status === 'verification-link-sent'
                            "
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{ trans('profile.email_verification.resent') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4 xl:col-start-1">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >{{ trans('profile.action.save') }}</Button
                        >
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </PageWrapper>
</template>
