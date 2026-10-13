<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CircleCheckBig,
    Hourglass,
    Plus,
    ShieldCheck,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
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

const props = defineProps<{
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

const cards = computed(() => [
    {
        label: 'Approved this month',
        value: String(props.metrics.approved_this_month.count),
        hint: moneyList(props.metrics.approved_this_month.value, 'None yet'),
        icon: CircleCheckBig,
        tone: 'bg-signal-soft text-lime-800 dark:bg-lime-950 dark:text-lime-200',
    },
    {
        label: 'Revenue protected',
        value: moneyList(props.metrics.revenue_protected),
        hint: 'All approved changes',
        icon: ShieldCheck,
        tone: 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-200',
    },
    {
        label: 'Outstanding payments',
        value: moneyList(props.metrics.outstanding_value),
        hint:
            props.metrics.avg_response_hours === null
                ? 'Avg. response n/a'
                : `Avg. response ${props.metrics.avg_response_hours} h`,
        icon: Wallet,
        tone: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            :eyebrow="$page.props.workspace?.name"
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
            <div
                class="card-lift hover-lift relative overflow-hidden rounded-2xl bg-ink p-5 text-white dark:bg-brand-950"
            >
                <div
                    class="bg-grid-light pointer-events-none absolute inset-0"
                    aria-hidden="true"
                />
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-white/70">Awaiting client</p>
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-white/10"
                            ><Hourglass class="size-4 text-signal"
                        /></span>
                    </div>
                    <p
                        class="mt-3 font-display text-4xl font-semibold text-signal"
                    >
                        {{ metrics.pending_count }}
                    </p>
                    <p class="mt-1 truncate text-xs text-white/60">
                        {{
                            moneyList(metrics.pending_value, 'Nothing pending')
                        }}
                    </p>
                </div>
            </div>
            <div
                v-for="card in cards"
                :key="card.label"
                class="card-lift hover-lift group rounded-2xl border bg-card p-5 hover:border-primary/30"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </p>
                    <span
                        :class="[
                            'flex size-8 items-center justify-center rounded-lg transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6',
                            card.tone,
                        ]"
                        ><component :is="card.icon" class="size-4"
                    /></span>
                </div>
                <p
                    class="mt-3 truncate font-display text-3xl font-semibold tracking-tight"
                >
                    {{ card.value }}
                </p>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ card.hint }}
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
                            class="group -mx-2 flex flex-wrap items-center gap-3 rounded-xl px-2 py-3 transition hover:translate-x-1 hover:bg-muted"
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
                            <span class="font-display text-sm font-semibold">{{
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
                <ol
                    v-else
                    class="relative space-y-4 border-l-2 border-border pl-4 text-sm"
                >
                    <li
                        v-for="event in activity"
                        :key="event.id"
                        class="relative before:absolute before:top-1.5 before:-left-[23px] before:size-3 before:rounded-full before:border-2 before:border-card before:bg-primary"
                    >
                        <Link
                            :href="event.url"
                            class="font-medium hover:underline"
                            >{{ event.event }}</Link
                        >
                        <p class="text-xs text-muted-foreground">
                            {{ event.actor }} · {{ event.at }}
                        </p>
                    </li>
                </ol>
            </Panel>
        </div>
    </div>
</template>
