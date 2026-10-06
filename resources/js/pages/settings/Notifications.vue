<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Notification settings',
                href: '/app/settings/notifications',
            },
        ],
    },
});

const props = defineProps<{ preferences: Record<string, boolean> }>();

const labels: Record<string, { title: string; description: string }> = {
    client_viewed_email: {
        title: 'Client opened a request',
        description: 'Email me the first time a client views a change request.',
    },
    client_decided_email: {
        title: 'Client approved or declined',
        description:
            'Recommended. You can start work or follow up straight away.',
    },
    client_questioned_email: {
        title: 'Client asked a question',
        description: 'Questions pause the approval, so a quick reply matters.',
    },
    payment_email: {
        title: 'Payment updates',
        description: 'When a client marks payment as sent.',
    },
    expired_email: {
        title: 'Request expired',
        description: 'When a link expires without a decision.',
    },
    weekly_digest: {
        title: 'Weekly digest',
        description: 'A Monday summary of open requests and approved revenue.',
    },
};

const form = useForm({ ...props.preferences });
</script>

<template>
    <Head title="Notification settings" />
    <h1 class="sr-only">Notification settings</h1>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Email notifications"
            description="In-app notifications are always on. Choose which ones also arrive by email."
        />
        <form
            class="space-y-4"
            @submit.prevent="
                form.put('/app/settings/notifications', {
                    preserveScroll: true,
                })
            "
        >
            <label
                v-for="(value, key) in preferences"
                :key="key"
                class="flex items-start gap-3"
            >
                <input
                    v-model="form[key]"
                    type="checkbox"
                    class="mt-1 size-4"
                />
                <span>
                    <span class="block text-sm font-medium">{{
                        labels[key]?.title ?? key
                    }}</span>
                    <span class="block text-xs text-muted-foreground">{{
                        labels[key]?.description
                    }}</span>
                </span>
            </label>
            <Button type="submit" :disabled="form.processing"
                >Save preferences</Button
            >
        </form>
    </div>
</template>
