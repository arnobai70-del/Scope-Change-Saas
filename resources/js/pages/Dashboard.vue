<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { moneyList } from '@/lib/format';
import type { ChangeRequestRow, Money } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: '/app/dashboard' }] },
});

defineProps<{
    metrics: {
        pending_value: Money[];
        pending_count: number;
        approved_this_month: { count: number; value: Money[] };
        revenue_protected: Money[];
        outstanding_value: Money[];
        avg_response_hours: number | null;
        counts: Record<string, number>;
    };
    queue: ChangeRequestRow[];
    activity: {
        id: number;
        event: string;
        actor: string;
        at: string;
        url: string;
    }[];
    hasProjects: boolean;
    hasClients: boolean;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Dashboard"
            description="What needs your attention and what you have protected."
        >
            <Button as-child>
                <Link href="/app/change-requests/create"
                    ><Plus class="size-4" /> New change request</Link
                >
            </Button>
        </PageHeader>

        <EmptyState
            v-if="!hasProjects"
            title="Set up your first project"
            description="Add a client and a project with its agreed scope. Then turn the next extra request into a priced change your client approves."
        >
            <Button v-if="!hasClients" as-child
                ><Link href="/app/clients?new=1">Add a client</Link></Button
            >
            <Button v-else as-child
                ><Link href="/app/projects?new=1"
                    >Create a project</Link
                ></Button
            >
        </EmptyState>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Awaiting client</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ metrics.pending_count }}
                </p>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ moneyList(metrics.pending_value, 'Nothing pending') }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Approved this month</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ metrics.approved_this_month.count }}
                </p>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{
                        moneyList(metrics.approved_this_month.value, 'None yet')
                    }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Revenue protected</p>
                <p class="mt-1 truncate text-2xl font-semibold">
                    {{ moneyList(metrics.revenue_protected) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    All approved changes
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    Outstanding payments
                </p>
                <p class="mt-1 truncate text-2xl font-semibold">
                    {{ moneyList(metrics.outstanding_value) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Avg. response
                    {{
                        metrics.avg_response_hours === null
                            ? 'n/a'
                            : `${metrics.avg_response_hours} h`
                    }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel
                title="Action queue"
                description="Questions first, then payments, then everything waiting."
                class="lg:col-span-2"
            >
                <p
                    v-if="queue.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nothing needs you right now.
                </p>
                <ul v-else class="divide-y">
                    <li v-for="item in queue" :key="item.id">
                        <Link
                            :href="`/app/change-requests/${item.id}`"
                            class="flex flex-wrap items-center gap-3 py-3 hover:bg-muted/50"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ item.title }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ item.reference }} ·
                                    {{ item.project.title
                                    }}<span v-if="item.client">
                                        · {{ item.client }}</span
                                    >
                                </p>
                            </div>
                            <span class="text-sm font-medium">{{
                                item.price?.formatted
                            }}</span>
                            <StatusBadge
                                :status="item.status"
                                :label="item.action ?? item.status_label"
                            />
                        </Link>
                    </li>
                </ul>
            </Panel>

            <Panel title="Recent activity">
                <p
                    v-if="activity.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No activity yet.
                </p>
                <ol v-else class="space-y-3 text-sm">
                    <li v-for="event in activity" :key="event.id">
                        <Link :href="event.url" class="hover:underline">{{
                            event.event
                        }}</Link>
                        <p class="text-xs text-muted-foreground">
                            {{ event.actor }} · {{ event.at }}
                        </p>
                    </li>
                </ol>
            </Panel>
        </div>
    </div>
</template>
