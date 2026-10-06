<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notifications', href: '/app/notifications' }],
    },
});

defineProps<{
    notifications: Paginated<{
        id: string;
        data: { kind?: string; headline?: string; url?: string };
        read: boolean;
        created_at: string | null;
    }>;
}>();

function readAll(): void {
    router.post('/app/notifications/read-all', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader title="Notifications">
            <Button variant="outline" @click="readAll">Mark all as read</Button>
        </PageHeader>
        <EmptyState
            v-if="notifications.data.length === 0"
            title="You are all caught up"
            description="We will let you know when a client views, approves, declines or asks a question."
        />
        <ul v-else class="divide-y rounded-xl border">
            <li v-for="n in notifications.data" :key="n.id">
                <a
                    :href="`/app/notifications/${n.id}`"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-muted/40"
                >
                    <span
                        :class="[
                            'size-2 shrink-0 rounded-full',
                            n.read ? 'bg-transparent' : 'bg-primary',
                        ]"
                        :aria-label="n.read ? 'Read' : 'Unread'"
                    />
                    <span
                        :class="[
                            'flex-1 text-sm',
                            n.read ? 'text-muted-foreground' : 'font-medium',
                        ]"
                        >{{ n.data.headline ?? 'Notification' }}</span
                    >
                    <span class="text-xs text-muted-foreground">{{
                        n.created_at
                    }}</span>
                </a>
            </li>
        </ul>
        <Pagination
            :prev="notifications.prev_page_url"
            :next="notifications.next_page_url"
            :page="notifications.current_page"
            :last="notifications.last_page"
        />
    </div>
</template>
