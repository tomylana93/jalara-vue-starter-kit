<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    destroy as destroyTemporaryAvatarUpload,
    store as storeAvatarUpload,
} from '@/actions/App/Http/Controllers/Profile/AvatarUploadController';
import ProfileController from '@/actions/App/Http/Controllers/Profile/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Uploader } from '@/components/uploader';
import type { UploaderExistingFile } from '@/components/uploader';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    avatar: UploaderExistingFile | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
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
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name, email address, and phone number"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <input
                type="hidden"
                name="temporary_avatar_upload_id"
                :value="temporaryAvatarUploadIds[0] ?? ''"
            />

            <div class="grid gap-3">
                <Label>Avatar</Label>

                <div v-if="props.avatar" class="flex items-center gap-3">
                    <Avatar class="size-16">
                        <AvatarImage
                            :src="props.avatar.source"
                            :alt="user.name"
                        />
                        <AvatarFallback>{{ avatarInitials }}</AvatarFallback>
                    </Avatar>

                    <Link
                        :href="ProfileController.destroyAvatar()"
                        method="delete"
                        as="button"
                        class="text-sm font-medium text-destructive hover:underline"
                    >
                        Remove avatar
                    </Link>
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
                    label-idle="Drop your JPEG or WebP avatar here, or browse"
                    preview-size="compact"
                    :messages="{
                        invalidType: 'Please choose a JPEG or WebP image.',
                        tooLarge: 'Avatar images must be 2 MiB or smaller.',
                        uploadFailed: 'Your avatar could not be uploaded.',
                        removeFailed:
                            'Your temporary avatar could not be removed.',
                    }"
                />
                <InputError :message="errors.temporary_avatar_upload_id" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="block w-full"
                    name="name"
                    :default-value="user.name"
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="block w-full"
                    name="email"
                    :default-value="user.email"
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone number</Label>
                <Input
                    id="phone"
                    type="tel"
                    class="block w-full"
                    name="phone"
                    :default-value="user.phone ?? ''"
                    autocomplete="tel"
                    placeholder="Phone number"
                />
                <InputError :message="errors.phone" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>
</template>
