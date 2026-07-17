<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    inheritAttrs: false,
    layout: {
        title: 'auth.verify_email.card.heading',
        description: 'auth.verify_email.card.description',
    },
});

const { trans } = useTrans();

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="trans('auth.verify_email.title')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ trans('auth.verify_email.resent') }}
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            {{ trans('auth.verify_email.action.resend') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ trans('auth.common.action.logout') }}
        </TextLink>
    </Form>
</template>
