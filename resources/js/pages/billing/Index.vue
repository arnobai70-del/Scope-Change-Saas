<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
        provider: string;
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
    provider: 'paddle' | 'paypal';
    paypalClientId: string | null;
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

type PayPalButtons = {
    isEligible: () => boolean;
    render: (container: HTMLElement) => Promise<void>;
    close: () => Promise<void>;
};
type PayPalGlobal = {
    FUNDING: { CARD: string };
    Buttons: (options: Record<string, unknown>) => PayPalButtons;
};

const cardPlan = ref<{ key: string; name: string; price: string } | null>(null);
const cardOpen = ref(false);
const cardContainer = ref<HTMLElement | null>(null);
const cardLoading = ref(false);
let cardButtons: PayPalButtons | null = null;

function loadPayPal(clientId: string): Promise<PayPalGlobal> {
    const existing = (window as unknown as { paypal?: PayPalGlobal }).paypal;

    if (existing) {
        return Promise.resolve(existing);
    }

    return new Promise((resolve, reject) => {
        const params = new URLSearchParams({
            'client-id': clientId,
            vault: 'true',
            intent: 'subscription',
            components: 'buttons',
            'enable-funding': 'card',
            'disable-funding': 'paylater,venmo',
        });
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?${params.toString()}`;
        script.async = true;
        script.onload = () =>
            resolve((window as unknown as { paypal: PayPalGlobal }).paypal);
        script.onerror = () =>
            reject(
                new Error(
                    'Could not load the card form. Check your connection and try again.',
                ),
            );
        document.head.appendChild(script);
    });
}

/**
 * Card payment on this page. PayPal draws the card fields in its own secure
 * frame, so card numbers never touch our server.
 */
async function openCardForm(planKey: string): Promise<void> {
    const selected = props.plans.find((p) => p.key === planKey);

    if (!selected || !props.paypalClientId) {
        toast.error('Card payments are not configured yet.');

        return;
    }

    cardPlan.value = {
        key: selected.key,
        name: selected.name,
        price:
            interval.value === 'year'
                ? `${selected.yearly.formatted} / year`
                : `${selected.monthly.formatted} / month`,
    };
    cardOpen.value = true;
    cardLoading.value = true;

    try {
        const paypal = await loadPayPal(props.paypalClientId);
        await nextTick();

        if (!cardContainer.value) {
            return;
        }

        const chosenInterval = interval.value;
        const options = {
            style: { layout: 'vertical', shape: 'rect', label: 'subscribe' },
            createSubscription: async () => {
                const created = await postJson<{ subscription_id: string }>(
                    '/app/billing/checkout',
                    { plan: planKey, interval: chosenInterval },
                );

                return created.subscription_id;
            },
            onApprove: async (data: { subscriptionID?: string }) => {
                const result = await postJson<{ active: boolean }>(
                    '/app/billing/paypal/confirm',
                    { subscription_id: data.subscriptionID ?? '' },
                );
                cardOpen.value = false;
                router.visit(
                    `/app/billing?checkout=${result.active ? 'success' : 'pending'}`,
                );
            },
            onError: () => {
                toast.error(
                    'The payment did not go through. Check the card details and try again.',
                );
            },
        };

        cardContainer.value.innerHTML = '';
        cardButtons = paypal.Buttons({
            ...options,
            fundingSource: paypal.FUNDING.CARD,
        });

        // Cards are not offered in every country; fall back to PayPal's buttons.
        if (!cardButtons.isEligible()) {
            cardButtons = paypal.Buttons(options);
        }

        await cardButtons.render(cardContainer.value);
    } catch (error) {
        toast.error(
            error instanceof Error ? error.message : 'Checkout failed.',
        );
        cardOpen.value = false;
    } finally {
        cardLoading.value = false;
    }
}

function onCardOpenChange(open: boolean): void {
    cardOpen.value = open;

    if (!open && cardButtons) {
        cardButtons.close().catch(() => undefined);
        cardButtons = null;
    }
}

async function checkout(plan: string): Promise<void> {
    if (props.provider === 'paypal') {
        await openCardForm(plan);

        return;
    }

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
} else if (props.checkoutStatus === 'pending') {
    toast.info(
        'PayPal is still confirming your subscription. Refresh this page in a minute.',
    );
} else if (props.checkoutStatus === 'canceled') {
    toast.info('Checkout was cancelled. You have not been charged.');
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
                    ><a
                        href="/app/billing/portal"
                        :target="
                            subscription.provider === 'paypal'
                                ? '_blank'
                                : undefined
                        "
                        rel="noopener"
                        >{{
                            subscription.provider === 'paypal'
                                ? 'Manage on PayPal'
                                : 'Manage payment method'
                        }}</a
                    ></Button
                >
                <Button
                    v-if="
                        subscription.cancels_at &&
                        subscription.provider !== 'paypal'
                    "
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
                    v-else-if="!subscription.cancels_at"
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
                class="card-lift hover-lift rounded-2xl border bg-card p-5 hover:border-primary/30"
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
                        'card-lift flex flex-col rounded-2xl border bg-card p-5',
                        p.key === plan ? 'ring-2 ring-primary' : '',
                    ]"
                >
                    <p class="font-semibold">{{ p.name }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ p.description }}
                    </p>
                    <p class="mt-3 font-display text-2xl font-semibold">
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
            <p
                v-if="provider === 'paypal'"
                class="text-xs text-muted-foreground"
            >
                Pay by debit or credit card right here. Card details go straight
                to PayPal's secure form and are never stored by us. Cancel any
                time.
            </p>
            <p v-else class="text-xs text-muted-foreground">
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

        <Dialog :open="cardOpen" @update:open="onCardOpenChange">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pay by card</DialogTitle>
                    <DialogDescription v-if="cardPlan">
                        {{ cardPlan.name }} plan, {{ cardPlan.price }}. Renews
                        automatically until you cancel.
                    </DialogDescription>
                </DialogHeader>
                <p
                    v-if="cardLoading"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Loading secure card form…
                </p>
                <div
                    ref="cardContainer"
                    class="min-h-12 rounded-lg bg-white p-3"
                />
                <p class="text-xs text-muted-foreground">
                    Secured by PayPal. We never see or store your card number.
                </p>
            </DialogContent>
        </Dialog>
    </div>
</template>
