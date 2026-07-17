<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    inheritAttrs: false,
    layout: {
        title: 'auth.forgot_password.card.heading',
        description: 'auth.forgot_password.card.description',
    },
});

const { trans } = useTrans();

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="trans('auth.forgot_password.title')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">{{
                    trans('auth.forgot_password.label.email')
                }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    :placeholder="
                        trans('auth.forgot_password.placeholder.email')
                    "
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    {{ trans('auth.forgot_password.action.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ trans('auth.forgot_password.prompt') }}</span>
            <TextLink :href="login()">{{
                trans('auth.forgot_password.link.login')
            }}</TextLink>
        </div>
    </div>
</template>
