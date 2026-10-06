<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Webhooks', href: '/admin/webhooks' },
        ],
    },
});

defineProps<{
    events: Paginated<{
        id: number;
        provider: string;
        external_id: string;
        event_type: string;
        status: string;
        attempts: number;
        error: string | null;
        payload_hash: string;
        created_at: string | null;
    }>;
    filters: { status: string };
}>();

const statuses = ['failed', 'pending', 'processed', 'ignored', 'all'];
</script>

<template>
    <Head title="Admin · Webhooks" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Webhook events"
            description="Payloads are not shown; only their hash."
        />
        <AdminNav />
        <div class="flex gap-1">
            <Button
                v-for="s in statuses"
                :key="s"
                size="sm"
                :variant="filters.status === s ? 'secondary' : 'ghost'"
                class="capitalize"
                @click="
                    router.get(
                        '/admin/webhooks',
                        { status: s },
                        { preserveState: true },
                    )
                "
                >{{ s }}</Button
            >
        </div>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-3 py-2">Event</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Attempts</th>
                        <th class="px-3 py-2">Error</th>
                        <th />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="e in events.data" :key="e.id">
                        <td class="px-3 py-2">
                            {{ e.event_type }}
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ e.provider }} · {{ e.external_id }} ·
                                {{ e.created_at }}
                            </p>
                        </td>
                        <td class="px-3 py-2 capitalize">{{ e.status }}</td>
                        <td class="px-3 py-2">{{ e.attempts }}</td>
                        <td
                            class="max-w-xs truncate px-3 py-2 text-xs"
                            :title="e.error ?? ''"
                        >
                            {{ e.error }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <Button
                                v-if="e.status !== 'processed'"
                                size="sm"
                                variant="outline"
                                @click="
                                    router.post(
                                        `/admin/webhooks/${e.id}/retry`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                "
                                >Retry</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="events.prev_page_url"
            :next="events.next_page_url"
            :page="events.current_page"
            :last="events.last_page"
        />
    </div>
</template>
