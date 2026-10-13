<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    Flag,
    LifeBuoy,
    ScrollText,
    Search,
    Users,
    Webhook,
} from '@lucide/vue';
import { computed } from 'vue';
import AdminNav from '@/components/app/AdminNav.vue';
import Panel from '@/components/app/Panel.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Admin', href: '/admin' }] },
});

const props = defineProps<{
    stats: Record<string, number>;
    funnel: Record<string, number>;
    recentUsers: {
        id: number;
        name: string;
        email: string;
        verified: boolean;
        created_at: string | null;
    }[];
    recentWorkspaces: {
        id: number;
        name: string;
        owner: string;
        plan: string;
        created_at: string | null;
    }[];
}>();

const mrr = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
}).format((props.stats.mrr_usd_minor ?? 0) / 100);

const headline = [
    { key: 'users', label: 'Users' },
    { key: 'active_users_30d', label: 'Active (30d)' },
    { key: 'workspaces', label: 'Workspaces' },
    { key: 'trials', label: 'On trial' },
    { key: 'paid', label: 'Paid' },
];

const health: {
    key: string;
    label: string;
    href: string | null;
    info?: boolean;
}[] = [
    { key: 'failed_jobs', label: 'Failed jobs', href: null },
    { key: 'pending_jobs', label: 'Queued jobs', href: null, info: true },
    {
        key: 'failed_webhooks',
        label: 'Failed webhooks',
        href: '/admin/webhooks',
    },
    { key: 'email_failures_7d', label: 'Email bounces (7d)', href: null },
    { key: 'open_tickets', label: 'Open tickets', href: '/admin/tickets' },
];

const actions = [
    {
        title: 'Users',
        text: 'Search, suspend, verify, reset passwords and manage admins.',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Workspaces',
        text: 'See plans and usage, extend trials, suspend or restore.',
        href: '/admin/workspaces',
        icon: Building2,
    },
    {
        title: 'Webhooks',
        text: 'Inspect payment and email webhooks and retry failures.',
        href: '/admin/webhooks',
        icon: Webhook,
    },
    {
        title: 'Support',
        text: 'Answer contact-form tickets and track their state.',
        href: '/admin/tickets',
        icon: LifeBuoy,
    },
    {
        title: 'Feature flags',
        text: 'Turn features on or off for the whole platform.',
        href: '/admin/flags',
        icon: Flag,
    },
    {
        title: 'SEO & analytics',
        text: 'Meta tags, Google Analytics, robots.txt and sitemap.xml.',
        href: '/admin/seo',
        icon: Search,
    },
    {
        title: 'Admin audit',
        text: 'Every admin action, who did it and when.',
        href: '/admin/audit',
        icon: ScrollText,
    },
];

const funnelRows = computed(() => {
    const entries = Object.entries(props.funnel);
    const max = Math.max(1, ...entries.map(([, count]) => count));

    return entries.map(([event, count]) => ({
        label: event.replace(/_/g, ' '),
        count,
        width: `${Math.max(4, Math.round((count / max) * 100))}%`,
    }));
});
</script>

<template>
    <Head title="Admin" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <section
            class="relative overflow-hidden rounded-3xl bg-ink p-6 text-white sm:p-8 dark:bg-brand-950"
        >
            <div
                class="bg-grid-light pointer-events-none absolute inset-0"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-brand-600/40 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.14em] text-signal uppercase"
                    >
                        Admin control center
                    </p>
                    <h1
                        class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        Platform overview
                    </h1>
                    <p class="mt-2 text-sm text-white/65">
                        Monthly recurring revenue at list price
                    </p>
                    <p
                        class="mt-1 font-display text-5xl font-semibold tracking-tight text-signal"
                    >
                        {{ mrr }}
                    </p>
                </div>
                <dl class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                    <div
                        v-for="item in headline"
                        :key="item.key"
                        class="rounded-2xl bg-white/10 px-4 py-3 transition hover:bg-white/15"
                    >
                        <dt class="text-xs text-white/60">{{ item.label }}</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold">
                            {{ stats[item.key] ?? 0 }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <AdminNav />

        <section aria-labelledby="health-heading">
            <h2
                id="health-heading"
                class="mb-3 font-display text-lg font-semibold"
            >
                System health
            </h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <component
                    :is="item.href ? Link : 'div'"
                    v-for="item in health"
                    :key="item.key"
                    :href="item.href ?? undefined"
                    :class="[
                        'card-lift hover-lift group rounded-2xl border p-4',
                        (stats[item.key] ?? 0) > 0 && !item.info
                            ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950'
                            : 'bg-card',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            {{ item.label }}
                        </p>
                        <span
                            :class="[
                                'size-2.5 rounded-full',
                                (stats[item.key] ?? 0) === 0 || item.info
                                    ? 'bg-lime-500'
                                    : 'animate-pulse bg-red-500',
                            ]"
                            aria-hidden="true"
                        />
                    </div>
                    <p class="mt-2 font-display text-3xl font-semibold">
                        {{ stats[item.key] ?? 0 }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            (stats[item.key] ?? 0) === 0 || item.info
                                ? 'All good'
                                : 'Needs attention'
                        }}
                    </p>
                </component>
            </div>
        </section>

        <section aria-labelledby="manage-heading">
            <h2
                id="manage-heading"
                class="mb-3 font-display text-lg font-semibold"
            >
                Manage the platform
            </h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="action in actions"
                    :key="action.href"
                    :href="action.href"
                    class="card-lift hover-lift group flex items-start gap-4 rounded-2xl border bg-card p-5 hover:border-primary/30"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 dark:bg-brand-950 dark:text-brand-200"
                    >
                        <component :is="action.icon" class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="flex items-center gap-1.5 font-display font-semibold"
                        >
                            {{ action.title }}
                            <ArrowRight
                                class="nudge size-4 text-muted-foreground"
                            />
                        </span>
                        <span class="mt-1 block text-sm text-muted-foreground">
                            {{ action.text }}
                        </span>
                    </span>
                </Link>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Activation funnel (30 days)">
                <ol class="space-y-3 text-sm">
                    <li v-for="row in funnelRows" :key="row.label">
                        <div class="mb-1 flex justify-between">
                            <span class="capitalize">{{ row.label }}</span>
                            <span class="font-semibold">{{ row.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-brand-600 to-lime-500 transition-all duration-500"
                                :style="{ width: row.width }"
                            />
                        </div>
                    </li>
                </ol>
            </Panel>

            <Panel title="Newest users">
                <template #actions>
                    <Link
                        href="/admin/users"
                        class="text-sm font-medium text-primary hover:underline"
                        >View all</Link
                    >
                </template>
                <p
                    v-if="recentUsers.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No users yet.
                </p>
                <ul v-else class="space-y-1">
                    <li v-for="u in recentUsers" :key="u.id">
                        <Link
                            :href="`/admin/users?q=${encodeURIComponent(u.email)}`"
                            class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:translate-x-1 hover:bg-muted"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-semibold text-white"
                                >{{ u.name.slice(0, 1).toUpperCase() }}</span
                            >
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                >
                                    {{ u.name }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ u.email }}</span
                                >
                            </span>
                            <span class="text-xs text-muted-foreground">{{
                                u.created_at
                            }}</span>
                        </Link>
                    </li>
                </ul>
            </Panel>

            <Panel title="Newest workspaces">
                <template #actions>
                    <Link
                        href="/admin/workspaces"
                        class="text-sm font-medium text-primary hover:underline"
                        >View all</Link
                    >
                </template>
                <p
                    v-if="recentWorkspaces.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No workspaces yet.
                </p>
                <ul v-else class="space-y-1">
                    <li v-for="w in recentWorkspaces" :key="w.id">
                        <Link
                            :href="`/admin/workspaces?q=${encodeURIComponent(w.name)}`"
                            class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:translate-x-1 hover:bg-muted"
                        >
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                >
                                    {{ w.name }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ w.owner }}</span
                                >
                            </span>
                            <span
                                class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold capitalize"
                                >{{ w.plan }}</span
                            >
                        </Link>
                    </li>
                </ul>
            </Panel>
        </div>
    </div>
</template>
