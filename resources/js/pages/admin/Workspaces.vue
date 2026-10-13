<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Workspaces', href: '/admin/workspaces' },
        ],
    },
});

type Row = {
    id: number;
    name: string;
    owner: string;
    plan: string;
    members: number;
    projects: number;
    change_requests: number;
    suspended: boolean;
    suspension_reason: string | null;
    trial_ends_at: string | null;
    created_at: string | null;
};

const props = defineProps<{
    workspaces: Paginated<Row>;
    filters: { q: string };
}>();
const q = ref(props.filters.q);

function extendTrial(w: Row): void {
    const days = prompt(
        `Extend the Pro trial for ${w.name} by how many days?`,
        '14',
    );

    if (days && Number(days) > 0) {
        router.put(
            `/admin/workspaces/${w.id}/trial`,
            { days: Number(days) },
            { preserveScroll: true },
        );
    }
}

function toggle(w: Row): void {
    if (w.suspended) {
        if (confirm(`Restore ${w.name}?`)) {
            router.put(
                `/admin/workspaces/${w.id}/suspension`,
                { suspend: false },
                { preserveScroll: true },
            );
        }

        return;
    }

    const reason = prompt(
        `Reason for suspending ${w.name} (shown to the workspace):`,
    );

    if (reason) {
        router.put(
            `/admin/workspaces/${w.id}/suspension`,
            { suspend: true, reason },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head title="Admin · Workspaces" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader eyebrow="Admin control center" title="Workspaces" />
        <AdminNav />
        <form
            @submit.prevent="
                router.get(
                    '/admin/workspaces',
                    { q: q || undefined },
                    { preserveState: true },
                )
            "
        >
            <Input
                v-model="q"
                type="search"
                placeholder="Search workspace name"
                aria-label="Search workspaces"
                class="max-w-sm"
            />
        </form>
        <div class="card-lift overflow-x-auto rounded-2xl border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-3 py-2">Workspace</th>
                        <th class="px-3 py-2">Plan</th>
                        <th class="px-3 py-2">Members</th>
                        <th class="px-3 py-2">Projects</th>
                        <th class="px-3 py-2">Requests</th>
                        <th />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="w in workspaces.data" :key="w.id">
                        <td class="px-3 py-2">
                            {{ w.name }}
                            <p class="text-xs text-muted-foreground">
                                {{ w.owner }} · {{ w.created_at }}
                            </p>
                            <p
                                v-if="w.suspended"
                                class="text-xs text-destructive"
                            >
                                Suspended: {{ w.suspension_reason }}
                            </p>
                        </td>
                        <td class="px-3 py-2">
                            <span
                                class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold capitalize"
                                >{{ w.plan }}</span
                            >
                            <p
                                v-if="w.trial_ends_at"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                Trial until {{ w.trial_ends_at }}
                            </p>
                        </td>
                        <td class="px-3 py-2">{{ w.members }}</td>
                        <td class="px-3 py-2">{{ w.projects }}</td>
                        <td class="px-3 py-2">{{ w.change_requests }}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="extendTrial(w)"
                                >Extend trial</Button
                            >
                            <Button
                                size="sm"
                                variant="ghost"
                                :class="w.suspended ? '' : 'text-destructive'"
                                @click="toggle(w)"
                                >{{
                                    w.suspended ? 'Restore' : 'Suspend'
                                }}</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="workspaces.prev_page_url"
            :next="workspaces.next_page_url"
            :page="workspaces.current_page"
            :last="workspaces.last_page"
        />
    </div>
</template>
