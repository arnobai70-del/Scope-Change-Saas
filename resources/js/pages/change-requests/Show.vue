<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, Download, ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import Field from '@/components/app/Field.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TextArea from '@/components/app/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ChangeRequestRow, Money, Revision } from '@/types';

type Detail = ChangeRequestRow & {
    public_id: string;
    recipient_name: string | null;
    recipient_email: string | null;
    created_by: string | null;
    first_viewed_at: string | null;
    decided_at: string | null;
    completed_at: string | null;
    reminders_muted: boolean;
};

const props = defineProps<{
    changeRequest: Detail;
    revision: Revision | null;
    revisions: Revision[];
    comments: {
        id: number;
        actor_type: string;
        author: string;
        body: string;
        internal: boolean;
        created_at: string;
    }[];
    payment: {
        status: string;
        status_label: string;
        amount: Money;
        reference: string | null;
        external_url: string | null;
        marked_sent_at: string | null;
        confirmed_at: string | null;
    } | null;
    events: {
        id: number;
        event: string;
        actor: string;
        at: string;
        at_utc: string;
        hash: string;
    }[];
    proofPacks: {
        id: number;
        version: number;
        status: string;
        checksum: string | null;
        generated_at: string | null;
        download_url: string | null;
    }[];
    can: Record<
        | 'edit'
        | 'send'
        | 'revoke'
        | 'revise'
        | 'start'
        | 'complete'
        | 'confirm_payment'
        | 'proof_pack'
        | 'proof_pack_plan'
        | 'delete',
        boolean
    >;
    sentLink: string | null;
}>();

defineOptions({
    layout: (props: { changeRequest: { id: number; reference: string } }) => ({
        breadcrumbs: [
            { title: 'Change requests', href: '/app/change-requests' },
            {
                title: props.changeRequest.reference,
                href: `/app/change-requests/${props.changeRequest.id}`,
            },
        ],
    }),
});

const base = `/app/change-requests/${props.changeRequest.id}`;
const commentForm = useForm({ body: '', internal: false });
const paymentForm = useForm({ reference: '' });
const showOlder = ref(false);

function post(path: string, confirmText?: string): void {
    if (confirmText && !confirm(confirmText)) {
        return;
    }

    router.post(`${base}/${path}`, {}, { preserveScroll: true });
}

function destroy(): void {
    if (confirm('Delete this draft? This cannot be undone.')) {
        router.delete(base);
    }
}

function sendComment(): void {
    commentForm.post(`${base}/comments`, {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
}

function confirmPayment(): void {
    paymentForm.post(`${base}/payment/confirm`, { preserveScroll: true });
}

async function copy(text: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        toast.success('Link copied.');
    } catch {
        toast.error('Copy failed. Select the link and copy it manually.');
    }
}
</script>

<template>
    <Head
        :title="`${changeRequest.reference} · ${changeRequest.title ?? ''}`"
    />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="changeRequest.title ?? changeRequest.reference"
            :description="`${changeRequest.reference} · ${changeRequest.project.title}${changeRequest.client ? ' · ' + changeRequest.client : ''}`"
        >
            <StatusBadge
                :status="changeRequest.status"
                :label="changeRequest.status_label"
            />
        </PageHeader>

        <div
            v-if="sentLink"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-50"
        >
            <p class="font-medium">Approval link (shown once)</p>
            <p class="mt-1 text-xs">
                For security we only store a fingerprint of this link. Copy it
                now if you want to share it yourself.
            </p>
            <div class="mt-3 flex gap-2">
                <Input
                    :model-value="sentLink"
                    readonly
                    aria-label="Approval link"
                    class="bg-white font-mono text-xs dark:bg-black"
                />
                <Button variant="outline" @click="copy(sentLink)"
                    ><Copy class="size-4" /> Copy</Button
                >
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button v-if="can.edit" as-child
                ><Link :href="`${base}/edit`">Edit draft</Link></Button
            >
            <Button
                v-if="can.send"
                @click="
                    post(
                        'send',
                        changeRequest.status === 'draft'
                            ? 'Send this request to the client? The revision will be locked.'
                            : 'Send a fresh link to the client? Any previous link stops working.',
                    )
                "
            >
                {{
                    changeRequest.status === 'draft'
                        ? 'Send to client'
                        : 'Resend link'
                }}
            </Button>
            <Button v-if="revision" variant="outline" as-child>
                <a :href="`${base}/preview`" target="_blank" rel="noopener"
                    ><ExternalLink class="size-4" /> Client preview</a
                >
            </Button>
            <Button
                v-if="can.revise"
                variant="outline"
                @click="
                    post(
                        'revise',
                        'Start a new revision? The current link will stop working once you send the new one.',
                    )
                "
                >Revise</Button
            >
            <Button v-if="can.start" variant="outline" @click="post('start')"
                >Start work</Button
            >
            <Button
                v-if="can.complete"
                variant="outline"
                @click="post('complete', 'Mark this change as completed?')"
                >Mark completed</Button
            >
            <Button
                v-if="can.revoke"
                variant="outline"
                @click="
                    post(
                        'revoke',
                        'Revoke the approval link? The client will no longer be able to respond.',
                    )
                "
                >Revoke link</Button
            >
            <Button
                v-if="can.delete"
                variant="ghost"
                class="text-destructive"
                @click="destroy"
                >Delete draft</Button
            >
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Panel
                    v-if="revision"
                    :title="`Revision ${revision.revision_no}`"
                    :description="
                        revision.locked_at
                            ? `Locked ${revision.locked_at}`
                            : 'Draft, not yet sent'
                    "
                >
                    <dl class="grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-muted-foreground">Price</dt>
                            <dd class="text-lg font-semibold">
                                {{ revision.price.formatted }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Timeline</dt>
                            <dd class="font-medium">
                                {{ revision.timeline_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Payment</dt>
                            <dd class="font-medium">
                                {{ revision.payment_rule_label }}
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="font-medium">Requested change</p>
                            <p class="mt-1 whitespace-pre-line">
                                {{ revision.description }}
                            </p>
                        </div>
                        <div v-if="revision.scope_reason">
                            <p class="font-medium">Why it is outside scope</p>
                            <p class="mt-1 whitespace-pre-line">
                                {{ revision.scope_reason }}
                            </p>
                        </div>
                        <div v-if="revision.scope_items.length">
                            <p class="font-medium">Linked scope items</p>
                            <ul class="mt-1 list-inside list-disc">
                                <li
                                    v-for="item in revision.scope_items"
                                    :key="item.id"
                                >
                                    {{ item.title }}
                                    <span class="text-muted-foreground"
                                        >({{ item.type_label }})</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <div v-if="revision.scope_excerpt">
                            <p class="font-medium">Scope excerpt</p>
                            <blockquote
                                class="mt-1 border-l-2 pl-3 whitespace-pre-line text-muted-foreground"
                            >
                                {{ revision.scope_excerpt }}
                            </blockquote>
                        </div>
                        <div v-if="revision.terms_note">
                            <p class="font-medium">Terms</p>
                            <p class="mt-1 whitespace-pre-line">
                                {{ revision.terms_note }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="revision.decision"
                        :class="[
                            'mt-5 rounded-lg p-3 text-sm',
                            revision.decision.decision === 'approved'
                                ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100'
                                : 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100',
                        ]"
                    >
                        <p class="font-medium">
                            {{
                                revision.decision.decision === 'approved'
                                    ? 'Approved'
                                    : 'Declined'
                            }}
                            by {{ revision.decision.client_name }} ({{
                                revision.decision.client_email
                            }})
                        </p>
                        <p class="text-xs">
                            {{ revision.decision.decided_at }} · UTC
                            {{ revision.decision.decided_at_utc }}
                        </p>
                        <p v-if="revision.decision.reason" class="mt-1">
                            “{{ revision.decision.reason }}”
                        </p>
                    </div>
                    <p
                        v-if="revision.snapshot_hash"
                        class="mt-4 font-mono text-xs break-all text-muted-foreground"
                    >
                        Snapshot SHA-256 {{ revision.snapshot_hash }}
                    </p>
                </Panel>

                <Panel
                    title="Conversation"
                    description="Replies are emailed to the client. Internal notes are never shown to them."
                >
                    <p
                        v-if="comments.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No messages yet.
                    </p>
                    <ul class="space-y-3">
                        <li
                            v-for="c in comments"
                            :key="c.id"
                            :class="[
                                'rounded-lg p-3 text-sm',
                                c.internal
                                    ? 'border border-dashed bg-amber-50/50 dark:bg-amber-950/30'
                                    : c.actor_type === 'client'
                                      ? 'bg-muted'
                                      : 'border',
                            ]"
                        >
                            <p class="text-xs text-muted-foreground">
                                {{ c.author
                                }}{{ c.internal ? ' · internal note' : '' }} ·
                                {{ c.created_at }}
                            </p>
                            <p class="mt-1 whitespace-pre-line">{{ c.body }}</p>
                        </li>
                    </ul>
                    <form class="mt-4 grid gap-2" @submit.prevent="sendComment">
                        <TextArea
                            v-model="commentForm.body"
                            :rows="3"
                            aria-label="Message"
                            :placeholder="
                                commentForm.internal
                                    ? 'Internal note for your team'
                                    : 'Reply to the client'
                            "
                            required
                            :maxlength="5000"
                        />
                        <p
                            v-if="commentForm.errors.body"
                            class="text-sm text-destructive"
                        >
                            {{ commentForm.errors.body }}
                        </p>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm"
                                ><input
                                    v-model="commentForm.internal"
                                    type="checkbox"
                                    class="size-4"
                                />
                                Internal note</label
                            >
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="commentForm.processing"
                                >{{
                                    commentForm.internal
                                        ? 'Add note'
                                        : 'Send reply'
                                }}</Button
                            >
                        </div>
                    </form>
                </Panel>

                <Panel v-if="revisions.length > 1" title="Earlier revisions">
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="showOlder = !showOlder"
                        >{{ showOlder ? 'Hide' : 'Show' }}
                        {{ revisions.length - 1 }} earlier</Button
                    >
                    <ul v-if="showOlder" class="mt-3 space-y-3 text-sm">
                        <li
                            v-for="rev in revisions.filter(
                                (x) => x.id !== revision?.id,
                            )"
                            :key="rev.id"
                            class="rounded-lg border p-3"
                        >
                            <p class="font-medium">
                                Revision {{ rev.revision_no }} ·
                                {{ rev.price.formatted }} ·
                                {{ rev.timeline_label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    rev.locked_at
                                        ? `Locked ${rev.locked_at}`
                                        : 'Never sent'
                                }}<span v-if="rev.decision">
                                    · {{ rev.decision.decision }} by
                                    {{ rev.decision.client_name }}</span
                                >
                            </p>
                        </li>
                    </ul>
                </Panel>
            </div>

            <div class="space-y-6">
                <Panel title="Details">
                    <dl
                        class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm"
                    >
                        <dt class="text-muted-foreground">Recipient</dt>
                        <dd class="truncate">
                            {{ changeRequest.recipient_name ?? '—' }}<br /><span
                                class="text-xs text-muted-foreground"
                                >{{ changeRequest.recipient_email }}</span
                            >
                        </dd>
                        <dt class="text-muted-foreground">Created by</dt>
                        <dd>{{ changeRequest.created_by ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Sent</dt>
                        <dd>{{ changeRequest.sent_at ?? '—' }}</dd>
                        <dt class="text-muted-foreground">First viewed</dt>
                        <dd>{{ changeRequest.first_viewed_at ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Expires</dt>
                        <dd>{{ changeRequest.expires_at ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Decided</dt>
                        <dd>{{ changeRequest.decided_at ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Completed</dt>
                        <dd>{{ changeRequest.completed_at ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Reminders</dt>
                        <dd>
                            {{
                                changeRequest.reminders_muted
                                    ? 'Muted by client'
                                    : 'On'
                            }}
                        </dd>
                    </dl>
                </Panel>

                <Panel v-if="payment" title="Payment">
                    <p class="text-lg font-semibold">
                        {{ payment.amount.formatted }}
                    </p>
                    <StatusBadge
                        :status="payment.status"
                        :label="payment.status_label"
                    />
                    <dl
                        class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm"
                    >
                        <template v-if="payment.marked_sent_at"
                            ><dt class="text-muted-foreground">
                                Client marked sent
                            </dt>
                            <dd>{{ payment.marked_sent_at }}</dd></template
                        >
                        <template v-if="payment.reference"
                            ><dt class="text-muted-foreground">Reference</dt>
                            <dd>{{ payment.reference }}</dd></template
                        >
                        <template v-if="payment.confirmed_at"
                            ><dt class="text-muted-foreground">Confirmed</dt>
                            <dd>{{ payment.confirmed_at }}</dd></template
                        >
                    </dl>
                    <form
                        v-if="can.confirm_payment"
                        class="mt-4 grid gap-2"
                        @submit.prevent="confirmPayment"
                    >
                        <Field
                            label="Payment reference (optional)"
                            for="payment-ref"
                            :error="paymentForm.errors.reference"
                        >
                            <Input
                                id="payment-ref"
                                v-model="paymentForm.reference"
                                maxlength="120"
                            />
                        </Field>
                        <Button type="submit" :disabled="paymentForm.processing"
                            >Confirm payment received</Button
                        >
                    </form>
                </Panel>

                <Panel
                    title="Proof Pack"
                    description="A PDF record of the baseline, revisions, decision and activity."
                >
                    <ul v-if="proofPacks.length" class="mb-3 space-y-2 text-sm">
                        <li
                            v-for="pack in proofPacks"
                            :key="pack.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <span
                                >v{{ pack.version }} ·
                                <span class="text-muted-foreground">{{
                                    pack.status === 'ready'
                                        ? pack.generated_at
                                        : pack.status
                                }}</span></span
                            >
                            <a
                                v-if="pack.download_url"
                                :href="pack.download_url"
                                class="inline-flex items-center gap-1 text-sm underline"
                                ><Download class="size-4" /> PDF</a
                            >
                        </li>
                    </ul>
                    <Button
                        v-if="can.proof_pack && can.proof_pack_plan"
                        variant="outline"
                        class="w-full"
                        @click="post('proof-pack')"
                        >Generate Proof Pack</Button
                    >
                    <p
                        v-else-if="can.proof_pack"
                        class="text-sm text-muted-foreground"
                    >
                        Proof Packs are available on paid plans.
                        <Link href="/app/billing" class="underline"
                            >Upgrade</Link
                        >
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Available after the request is sent.
                    </p>
                </Panel>

                <Panel
                    title="Activity"
                    description="Hash-chained and append-only."
                >
                    <ol class="space-y-3 text-sm">
                        <li v-for="e in events" :key="e.id">
                            <p>{{ e.event }}</p>
                            <p
                                class="text-xs text-muted-foreground"
                                :title="`UTC ${e.at_utc} · #${e.hash}`"
                            >
                                {{ e.actor }} · {{ e.at }}
                            </p>
                        </li>
                    </ol>
                </Panel>
            </div>
        </div>
    </div>
</template>
