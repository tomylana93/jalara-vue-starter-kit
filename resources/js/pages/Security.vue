<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Security/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

const { trans } = useTrans();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'settings.security.heading',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <PageWrapper
        :title="trans('settings.security.heading')"
        :description="trans('settings.security.description')"
    >
        <Head :title="trans('settings.security.title')" />

        <SettingsLayout>
            <div class="space-y-12">
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        :title="trans('security.password.heading')"
                        :description="trans('security.password.description')"
                    />

                    <Form
                        v-bind="SecurityController.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">{{
                                trans('security.password.label.current')
                            }}</Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                                :placeholder="
                                    trans(
                                        'security.password.placeholder.current',
                                    )
                                "
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">{{
                                trans('security.password.label.new')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="
                                    trans('security.password.placeholder.new')
                                "
                                :passwordrules="props.passwordRules"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">{{
                                trans('security.password.label.confirm')
                            }}</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="
                                    trans(
                                        'security.password.placeholder.confirm',
                                    )
                                "
                                :passwordrules="props.passwordRules"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-password-button"
                            >
                                {{ trans('security.password.action.save') }}
                            </Button>
                        </div>
                    </Form>
                </div>

                <ManageTwoFactor
                    :canManageTwoFactor="canManageTwoFactor"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />

                <ManagePasskeys
                    :canManagePasskeys="canManagePasskeys"
                    :passkeys="passkeys"
                />
            </div>
        </SettingsLayout>
    </PageWrapper>
</template>
