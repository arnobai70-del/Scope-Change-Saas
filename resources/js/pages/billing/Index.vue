<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import { Button } from '@/components/ui/button';
import { postJson } from '@/lib/http';
import type { Money } from '@/types';

type Limit = { used: number; limit: number | null };

defineOptions({
    layout: { breadcrumbs: [{ title: 'Billing', href: '/app/billing' }] },
});

const props = defineProps<{
    plan: string;
    plans: {
        key: string;
        name: string;
        description: string;
        monthly: Money;
        yearly: Money;
        highlights: string[];
    }[];
    trialEndsAt: string | null;
    subscription: {
        plan: string;
        status: string;
        interval: string | null;
        renews_at: string | null;
        cancels_at: string | null;
    } | null;
    usage: {
        active_projects: Limit;
        change_requests_per_month: Limit;
        seats: Limit;
    };
    invoices: {
        id: number;
        number: string | null;
        status: string;
        total: Money;
        billed_at: string | null;
    }[];
    checkoutConfigured: boolean;
    isOwner: boolean;
    checkoutStatus: string | null;
}>();

const interval = ref<'month' | 'year'>('year');
const loading = ref<string | null>(null);

type PaddleGlobal = {
    Environment: { set: (env: string) => void };
    Initialize: (options: { token: string }) => void;
    Checkout: { open: (options: Record<string, unknown>) => void };
};

function loadPaddle(): Promise<PaddleGlobal> {
    const existing = (window as unknown as { Paddle?: PaddleGlobal }).Paddle;

    if (existing) {
        return Promise.resolve(existing);
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';
        script.async = true;
        script.onload = () =>
            resolve((window as unknown as { Paddle: PaddleGlobal }).Paddle);
        script.onerror = () =>
            reject(
                new Error(
                    'Could not load the checkout. Check your connection and try again.',
                ),
            );
        document.head.appendChild(script);
    });
}

let initialized = false;

async function checkout(plan: string): Promise<void> {
    loading.value = plan;

    try {
        const options = await postJson<{
            environment: string;
            token: string;
            items: unknown[];
            customer: unknown;
            customData: unknown;
            settings: unknown;
        }>('/app/billing/checkout', { plan, interval: interval.value });
        const paddle = await loadPaddle();

        if (!initialized) {
            if (options.environment === 'sandbox') {
                paddle.Environment.set('sandbox');
            }

            paddle.Initialize({ token: options.token });
            initialized = true;
        }

        paddle.Checkout.open({
            items: options.items,
            customer: options.customer,
            customData: options.customData,
            settings: options.settings,
        });
    } catch (error) {
        toast.error(
            error instanceof Error ? error.message : 'Checkout failed.',
        );
    } finally {
        loading.value = null;
    }
}

function cancel(): void {
    if (
        confirm(
            'Cancel your subscription? You keep access until the end of the current period.',
        )
    ) {
        router.post('/app/billing/cancel', {}, { preserveScroll: true });
    }
}

function limitLabel(limit: Limit): string {
    return `${limit.used} / ${limit.limit ?? '∞'}`;
}

const usageRows = [
    { key: 'active_projects', label: 'Active projects' },
    { key: 'change_requests_per_month', label: 'Change requests this month' },
    { key: 'seats', label: 'Team seats' },
] as const;

if (props.checkoutStatus === 'success') {
    toast.success(
        'Thanks! Your subscription is being activated. This page updates within a minute.',
    );
}
</script>

<template>
    <Head title="Billing" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Billing"
            :description="`You are on the ${plan} plan${trialEndsAt ? `, trial until ${trialEndsAt}` : ''}.`"
        >
            <template v-if="subscription && isOwner">
                <Button variant="outline" as-child
                    ><a href="/app/billing/portal"
                        >Manage payment method</a
                    ></Button
                >
                <Button
                    v-if="subscription.cancels_at"
                    @click="
                        router.post(
                            '/app/billing/resume',
                            {},
                            { preserveScroll: true },
                        )
                    "
                    >Resume subscription</Button
                >
                <Button
                    v-else
                    variant="ghost"
                    class="text-destructive"
                    @click="cancel"
                    >Cancel subscription</Button
                >
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="row in usageRows"
                :key="row.key"
                class="rounded-xl border p-4"
            >
                <p class="text-sm text-muted-foreground">{{ row.label }}</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ limitLabel(usage[row.key]) }}
                </p>
            </div>
        </div>

        <Panel v-if="subscription" title="Subscription">
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 text-sm">
                <dt class="text-muted-foreground">Plan</dt>
                <dd class="capitalize">
                    {{ subscription.plan }} ({{ subscription.interval }}ly)
                </dd>
                <dt class="text-muted-foreground">Status</dt>
                <dd class="capitalize">
                    {{ subscription.status.replace('_', ' ') }}
                </dd>
                <template v-if="subscription.cancels_at"
                    ><dt class="text-muted-foreground">Ends</dt>
                    <dd>{{ subscription.cancels_at }}</dd></template
                >
                <template v-else-if="subscription.renews_at"
                    ><dt class="text-muted-foreground">Renews</dt>
                    <dd>{{ subscription.renews_at }}</dd></template
                >
            </dl>
            <p class="mt-3 text-xs text-muted-foreground">
                To change plans, use Manage payment method or contact support.
                Changes are prorated by our payment provider.
            </p>
        </Panel>

        <template v-else>
            <div class="flex items-center gap-2">
                <Button
                    size="sm"
                    :variant="interval === 'month' ? 'secondary' : 'ghost'"
                    @click="interval = 'month'"
                    >Monthly</Button
                >
                <Button
                    size="sm"
                    :variant="interval === 'year' ? 'secondary' : 'ghost'"
                    @click="interval = 'year'"
                    >Yearly (2 months free)</Button
                >
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="p in plans"
                    :key="p.key"
                    :class="[
                        'flex flex-col rounded-xl border p-5',
                        p.key === plan ? 'ring-2 ring-primary' : '',
                    ]"
                >
                    <p class="font-semibold">{{ p.name }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ p.description }}
                    </p>
                    <p class="mt-3 text-2xl font-semibold">
                        {{
                            interval === 'year'
                                ? p.yearly.formatted
                                : p.monthly.formatted
                        }}<span
                            class="text-sm font-normal text-muted-foreground"
                        >
                            / {{ interval }}</span
                        >
                    </p>
                    <ul class="mt-3 flex-1 space-y-1 text-sm">
                        <li v-for="h in p.highlights" :key="h">✓ {{ h }}</li>
                    </ul>
                    <Button
                        v-if="p.key !== 'free'"
                        class="mt-4"
                        :disabled="
                            !isOwner || !checkoutConfigured || loading !== null
                        "
                        @click="checkout(p.key)"
                    >
                        {{
                            loading === p.key
                                ? 'Opening checkout…'
                                : `Choose ${p.name}`
                        }}
                    </Button>
                </div>
            </div>
            <p v-if="!isOwner" class="text-sm text-muted-foreground">
                Only the workspace owner can change the subscription.
            </p>
            <p
                v-else-if="!checkoutConfigured"
                class="text-sm text-muted-foreground"
            >
                Online checkout is not configured on this installation yet.
            </p>
            <p class="text-xs text-muted-foreground">
                Payments are processed by Paddle, our merchant of record, which
                handles tax and invoices.
            </p>
        </template>

        <Panel title="Invoices">
            <p
                v-if="invoices.length === 0"
                class="text-sm text-muted-foreground"
            >
                No invoices yet.
            </p>
            <table v-else class="w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-1 font-medium">Date</th>
                        <th class="py-1 font-medium">Invoice</th>
                        <th class="py-1 font-medium">Status</th>
                        <th class="py-1 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="i in invoices" :key="i.id">
                        <td class="py-1.5">{{ i.billed_at }}</td>
                        <td>{{ i.number ?? '—' }}</td>
                        <td class="capitalize">{{ i.status }}</td>
                        <td class="text-right">{{ i.total.formatted }}</td>
                    </tr>
                </tbody>
            </table>
        </Panel>
    </div>
</template>
