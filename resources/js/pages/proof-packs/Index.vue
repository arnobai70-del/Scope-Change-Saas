<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Proof Packs', href: '/app/proof-packs' }],
    },
});

defineProps<{
    packs: Paginated<{
        id: number;
        version: number;
        status: string;
        checksum: string | null;
        generated_at: string | null;
        reference: string;
        title: string | null;
        project: string;
        change_request_id: number;
        download_url: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Proof Packs" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Proof Packs"
            description="PDF evidence records for your change requests. Generate one from any sent request."
        />
        <EmptyState
            v-if="packs.data.length === 0"
            title="No Proof Packs yet"
            description="Open a sent change request and choose Generate Proof Pack."
        />
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Change request</th>
                        <th class="px-4 py-2 font-medium">Version</th>
                        <th class="hidden px-4 py-2 font-medium md:table-cell">
                            Checksum
                        </th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Download</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="pack in packs.data" :key="pack.id">
                        <td class="px-4 py-3">
                            <Link
                                :href="`/app/change-requests/${pack.change_request_id}`"
                                class="font-medium hover:underline"
                                >{{ pack.title ?? pack.reference }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ pack.reference }} · {{ pack.project }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            v{{ pack.version }}
                            <p class="text-xs text-muted-foreground">
                                {{ pack.generated_at }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 font-mono text-xs md:table-cell"
                        >
                            {{ pack.checksum?.slice(0, 16) }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="
                                    pack.status === 'ready'
                                        ? 'completed'
                                        : pack.status
                                "
                                :label="pack.status"
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a
                                v-if="pack.download_url"
                                :href="pack.download_url"
                                class="inline-flex items-center gap-1 underline"
                                ><Download class="size-4" /> PDF</a
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="packs.prev_page_url"
            :next="packs.next_page_url"
            :page="packs.current_page"
            :last="packs.last_page"
        />
    </div>
</template>
