<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ChangeRequestRow, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Change requests', href: '/app/change-requests' },
        ],
    },
});

const props = defineProps<{
    changeRequests: Paginated<ChangeRequestRow>;
    filters: { status: string; q: string };
}>();

const search = ref(props.filters.q);
const tabs = [
    { value: 'all', label: 'All' },
    { value: 'awaiting', label: 'Awaiting client' },
    { value: 'approved', label: 'Approved' },
    { value: 'draft', label: 'Drafts' },
    { value: 'closed', label: 'Closed' },
];

function apply(status = props.filters.status): void {
    router.get(
        '/app/change-requests',
        {
            status: status === 'all' ? undefined : status,
            q: search.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Change requests" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Change requests"
            description="Every extra request, priced and tracked to a decision."
        >
            <Button as-child
                ><Link href="/app/change-requests/create"
                    ><Plus class="size-4" /> New change request</Link
                ></Button
            >
        </PageHeader>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex gap-1 overflow-x-auto" role="tablist">
                <Button
                    v-for="tab in tabs"
                    :key="tab.value"
                    size="sm"
                    role="tab"
                    :aria-selected="filters.status === tab.value"
                    :variant="
                        filters.status === tab.value ? 'secondary' : 'ghost'
                    "
                    @click="apply(tab.value)"
                >
                    {{ tab.label }}
                </Button>
            </div>
            <form @submit.prevent="apply()">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search reference or title"
                    aria-label="Search change requests"
                    class="w-64"
                />
            </form>
        </div>

        <EmptyState
            v-if="changeRequests.data.length === 0"
            title="No change requests here"
            description="When a client asks for something outside the agreed scope, create a change request and send it for approval."
        >
            <Button as-child
                ><Link href="/app/change-requests/create"
                    >Create change request</Link
                ></Button
            >
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Request</th>
                        <th class="hidden px-4 py-2 font-medium md:table-cell">
                            Project
                        </th>
                        <th class="px-4 py-2 font-medium">Price</th>
                        <th class="hidden px-4 py-2 font-medium lg:table-cell">
                            Updated
                        </th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="cr in changeRequests.data"
                        :key="cr.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="`/app/change-requests/${cr.id}`"
                                class="font-medium hover:underline"
                                >{{ cr.title }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ cr.reference
                                }}<span v-if="cr.client">
                                    · {{ cr.client }}</span
                                >
                            </p>
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            {{ cr.project.title }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ cr.price?.formatted }}
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                        >
                            {{ cr.updated_at }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="cr.status"
                                :label="cr.status_label"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="changeRequests.prev_page_url"
            :next="changeRequests.next_page_url"
            :page="changeRequests.current_page"
            :last="changeRequests.last_page"
        />
    </div>
</template>
