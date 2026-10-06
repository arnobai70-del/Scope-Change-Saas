<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Audit', href: '/admin/audit' },
        ],
    },
});

defineProps<{
    events: Paginated<{
        id: number;
        event: string;
        actor: string | null;
        entity: string;
        metadata: Record<string, unknown> | null;
        at: string;
    }>;
}>();
</script>

<template>
    <Head title="Admin · Audit" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Admin audit log"
            description="Every action taken from this panel."
        />
        <AdminNav />
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Admin</th>
                        <th class="px-3 py-2">Action</th>
                        <th class="px-3 py-2">Target</th>
                        <th class="px-3 py-2">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="e in events.data" :key="e.id">
                        <td class="px-3 py-2 text-xs whitespace-nowrap">
                            {{ e.at }}
                        </td>
                        <td class="px-3 py-2">{{ e.actor }}</td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{ e.event }}
                        </td>
                        <td class="px-3 py-2">{{ e.entity }}</td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{ e.metadata ? JSON.stringify(e.metadata) : '' }}
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
