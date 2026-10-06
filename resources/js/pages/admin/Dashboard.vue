<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Admin', href: '/admin' }] },
});

const props = defineProps<{
    stats: Record<string, number>;
    funnel: Record<string, number>;
}>();

const cards = [
    { key: 'users', label: 'Users' },
    { key: 'active_users_30d', label: 'Active users (30d)' },
    { key: 'workspaces', label: 'Workspaces' },
    { key: 'trials', label: 'On trial' },
    { key: 'paid', label: 'Paid subscriptions' },
    { key: 'failed_jobs', label: 'Failed jobs' },
    { key: 'pending_jobs', label: 'Queued jobs' },
    { key: 'failed_webhooks', label: 'Failed webhooks' },
    { key: 'email_failures_7d', label: 'Email bounces (7d)' },
    { key: 'open_tickets', label: 'Open tickets' },
];

const mrr = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
}).format((props.stats.mrr_usd_minor ?? 0) / 100);
</script>

<template>
    <Head title="Admin" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Operations"
            description="Platform health and growth at a glance."
        />
        <AdminNav />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">MRR (list price)</p>
                <p class="mt-1 text-2xl font-semibold">{{ mrr }}</p>
            </div>
            <div
                v-for="card in cards"
                :key="card.key"
                class="rounded-xl border p-4"
            >
                <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ stats[card.key] ?? 0 }}
                </p>
            </div>
        </div>
        <Panel title="Activation funnel (30 days)">
            <ol class="space-y-2 text-sm">
                <li
                    v-for="(count, event) in funnel"
                    :key="event"
                    class="flex justify-between border-b pb-1"
                >
                    <span class="capitalize">{{
                        String(event).replace(/_/g, ' ')
                    }}</span
                    ><span class="font-medium">{{ count }}</span>
                </li>
            </ol>
        </Panel>
    </div>
</template>
