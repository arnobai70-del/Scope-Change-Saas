<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import TextArea from '@/components/app/TextArea.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Baseline, Revision } from '@/types';

type ProjectOption = {
    id: number;
    title: string;
    client: string;
    currency: string;
    recipient_name: string | null;
    recipient_email: string | null;
    baseline: Baseline | null;
};

type TemplateOption = {
    id: number;
    name: string;
    system: boolean;
    content: {
        title?: string | null;
        description?: string | null;
        scope_reason?: string | null;
        terms_note?: string | null;
    };
};

const props = defineProps<{
    mode: 'create' | 'edit';
    projects: ProjectOption[];
    selectedProjectId: number;
    changeRequest: {
        id: number;
        reference: string;
        recipient_name: string | null;
        recipient_email: string | null;
    } | null;
    revision: Revision | null;
    currencies: string[];
    paymentRules: { value: string; label: string }[];
    timelineTypes: string[];
    templates: TemplateOption[];
    defaults: {
        payment_url: string | null;
        payment_instructions: string | null;
        expiry_days: number;
    };
}>();

defineOptions({
    layout: (props: {
        mode: string;
        changeRequest: { id: number; reference: string } | null;
    }) => ({
        breadcrumbs: [
            { title: 'Change requests', href: '/app/change-requests' },
            props.changeRequest
                ? {
                      title: props.changeRequest.reference,
                      href: `/app/change-requests/${props.changeRequest.id}`,
                  }
                : { title: 'New', href: '/app/change-requests/create' },
        ],
    }),
});

const initialProject =
    props.projects.find((p) => p.id === props.selectedProjectId) ??
    props.projects[0];
const r = props.revision;

const form = useForm({
    project_id: initialProject?.id ?? null,
    title: r?.title ?? '',
    description: r?.description ?? '',
    scope_reason: r?.scope_reason ?? '',
    scope_excerpt: r?.scope_excerpt ?? '',
    scope_item_ids: r?.scope_items.map((item) => item.id) ?? ([] as number[]),
    price: r?.price.decimal ?? '',
    currency: r?.currency ?? initialProject?.currency ?? props.currencies[0],
    timeline_type: r?.timeline.type ?? 'none',
    timeline_value: (r?.timeline.value ?? '') as string | number,
    timeline_note: r?.timeline.note ?? '',
    payment_rule: r?.payment_rule ?? 'none',
    payment_url: r?.payment_url ?? props.defaults.payment_url ?? '',
    payment_instructions:
        r?.payment_instructions ?? props.defaults.payment_instructions ?? '',
    terms_note: r?.terms_note ?? '',
    recipient_name:
        props.changeRequest?.recipient_name ??
        initialProject?.recipient_name ??
        '',
    recipient_email:
        props.changeRequest?.recipient_email ??
        initialProject?.recipient_email ??
        '',
    send_now: false,
    notify_client: true,
});

const project = computed(
    () => props.projects.find((p) => p.id === form.project_id) ?? null,
);
const scopeItems = computed(() => project.value?.baseline?.items ?? []);
const timelineLabels: Record<string, string> = {
    none: 'No change to timeline',
    days: 'Adds working days',
    weeks: 'Adds weeks',
    date: 'New delivery date',
};

watch(
    () => form.project_id,
    () => {
        if (props.mode === 'create' && project.value) {
            form.currency = project.value.currency;
            form.recipient_name = project.value.recipient_name ?? '';
            form.recipient_email = project.value.recipient_email ?? '';
            form.scope_item_ids = [];
        }
    },
);

function applyTemplate(id: string): void {
    const template = props.templates.find((t) => String(t.id) === id);

    if (!template) {
        return;
    }

    form.title = template.content.title ?? form.title;
    form.description = template.content.description ?? form.description;
    form.scope_reason = template.content.scope_reason ?? form.scope_reason;
    form.terms_note = template.content.terms_note ?? form.terms_note;
}

function toggleItem(id: number | undefined): void {
    if (id === undefined) {
        return;
    }

    form.scope_item_ids = form.scope_item_ids.includes(id)
        ? form.scope_item_ids.filter((x) => x !== id)
        : [...form.scope_item_ids, id];
}

function save(sendNow: boolean, notifyClient = true): void {
    form.send_now = sendNow;
    form.notify_client = notifyClient;

    if (sendNow && notifyClient && !form.recipient_email) {
        form.setError(
            'recipient_email',
            'Add the client email to send, or use "Create link only".',
        );

        return;
    }

    const options = { preserveScroll: true };

    if (props.mode === 'edit' && props.changeRequest) {
        form.put(`/app/change-requests/${props.changeRequest.id}`, options);
    } else {
        form.post('/app/change-requests', options);
    }
}
</script>

<template>
    <Head
        :title="
            mode === 'create'
                ? 'New change request'
                : `Edit ${changeRequest?.reference}`
        "
    />
    <form class="flex flex-1 flex-col gap-6 p-4" @submit.prevent="save(false)">
        <PageHeader
            :title="
                mode === 'create'
                    ? 'New change request'
                    : `Edit ${changeRequest?.reference} · revision ${revision?.revision_no}`
            "
            description="Describe the extra work, link it to the agreed scope, and set price, timeline and payment terms."
        >
            <NativeSelect
                v-if="templates.length"
                class="w-56"
                aria-label="Apply a template"
                :model-value="''"
                @update:model-value="(v) => applyTemplate(String(v))"
            >
                <option value="">Start from a template…</option>
                <option
                    v-for="t in templates"
                    :key="t.id"
                    :value="String(t.id)"
                >
                    {{ t.name }}{{ t.system ? '' : ' (yours)' }}
                </option>
            </NativeSelect>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Panel title="What changed">
                    <div class="grid gap-4">
                        <Field
                            v-if="mode === 'create'"
                            label="Project"
                            for="cr-project"
                            :error="form.errors.project_id"
                        >
                            <NativeSelect
                                id="cr-project"
                                v-model="form.project_id"
                            >
                                <option
                                    v-for="p in projects"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.title }} · {{ p.client }}
                                </option>
                            </NativeSelect>
                        </Field>
                        <Field
                            label="Title"
                            for="cr-title"
                            :error="form.errors.title"
                            hint="Short and specific, as the client would say it."
                        >
                            <Input
                                id="cr-title"
                                v-model="form.title"
                                required
                                maxlength="160"
                                placeholder="Add a blog section to the website"
                            />
                        </Field>
                        <Field
                            label="Requested change"
                            for="cr-description"
                            :error="form.errors.description"
                            hint="Paste the client's message or describe the extra work."
                        >
                            <TextArea
                                id="cr-description"
                                v-model="form.description"
                                :rows="6"
                                required
                                :maxlength="10000"
                                :invalid="!!form.errors.description"
                            />
                        </Field>
                        <Field
                            label="Why it is outside the agreed scope"
                            for="cr-reason"
                            :error="form.errors.scope_reason"
                        >
                            <TextArea
                                id="cr-reason"
                                v-model="form.scope_reason"
                                :rows="3"
                                :maxlength="5000"
                                placeholder="The agreed scope covered five pages; a blog and CMS were excluded."
                            />
                        </Field>
                    </div>
                </Panel>

                <Panel
                    title="Link to the agreed scope"
                    :description="
                        project?.baseline
                            ? `Scope v${project.baseline.version}${project.baseline.locked ? ' (locked)' : ' (draft)'}`
                            : 'This project has no scope yet.'
                    "
                >
                    <div v-if="scopeItems.length" class="grid gap-2">
                        <label
                            v-for="item in scopeItems"
                            :key="item.id"
                            class="flex items-start gap-3 rounded-md border p-2 text-sm hover:bg-muted/40"
                        >
                            <input
                                type="checkbox"
                                class="mt-0.5 size-4"
                                :checked="
                                    item.id !== undefined &&
                                    form.scope_item_ids.includes(item.id)
                                "
                                @change="toggleItem(item.id)"
                            />
                            <span
                                ><span class="text-xs text-muted-foreground">{{
                                    item.type_label
                                }}</span
                                ><br />{{ item.title }}</span
                            >
                        </label>
                        <InputError :message="form.errors.scope_item_ids" />
                    </div>
                    <Field
                        label="Scope excerpt (optional)"
                        for="cr-excerpt"
                        :error="form.errors.scope_excerpt"
                        class="mt-4"
                    >
                        <TextArea
                            id="cr-excerpt"
                            v-model="form.scope_excerpt"
                            :rows="2"
                            :maxlength="5000"
                            placeholder="Quote the relevant line of your proposal or contract"
                        />
                    </Field>
                </Panel>

                <Panel title="Terms note (optional)">
                    <TextArea
                        id="cr-terms"
                        v-model="form.terms_note"
                        :rows="3"
                        :maxlength="3000"
                        aria-label="Terms note"
                        placeholder="Anything the client agrees to by approving, e.g. content due by Friday."
                    />
                    <InputError :message="form.errors.terms_note" />
                </Panel>
            </div>

            <div class="space-y-6">
                <Panel title="Price and timeline">
                    <div class="grid gap-4">
                        <div class="grid grid-cols-[1fr_7rem] gap-2">
                            <Field
                                label="Price"
                                for="cr-price"
                                :error="form.errors.price"
                            >
                                <Input
                                    id="cr-price"
                                    v-model="form.price"
                                    inputmode="decimal"
                                    required
                                    placeholder="0.00"
                                />
                            </Field>
                            <Field
                                label="Currency"
                                for="cr-currency"
                                :error="form.errors.currency"
                            >
                                <NativeSelect
                                    id="cr-currency"
                                    v-model="form.currency"
                                >
                                    <option
                                        v-for="c in currencies"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ c }}
                                    </option>
                                </NativeSelect>
                            </Field>
                        </div>
                        <Field
                            label="Timeline impact"
                            for="cr-timeline"
                            :error="form.errors.timeline_type"
                        >
                            <NativeSelect
                                id="cr-timeline"
                                v-model="form.timeline_type"
                            >
                                <option
                                    v-for="t in timelineTypes"
                                    :key="t"
                                    :value="t"
                                >
                                    {{ timelineLabels[t] ?? t }}
                                </option>
                            </NativeSelect>
                        </Field>
                        <Field
                            v-if="form.timeline_type !== 'none'"
                            :label="
                                form.timeline_type === 'date'
                                    ? 'New delivery date'
                                    : `Number of ${form.timeline_type}`
                            "
                            for="cr-timeline-value"
                            :error="form.errors.timeline_value"
                        >
                            <Input
                                id="cr-timeline-value"
                                v-model="form.timeline_value"
                                :type="
                                    form.timeline_type === 'date'
                                        ? 'date'
                                        : 'number'
                                "
                                min="1"
                                max="365"
                                required
                            />
                        </Field>
                        <Field
                            label="Timeline note"
                            for="cr-timeline-note"
                            :error="form.errors.timeline_note"
                        >
                            <Input
                                id="cr-timeline-note"
                                v-model="form.timeline_note"
                                maxlength="255"
                                placeholder="Optional"
                            />
                        </Field>
                    </div>
                </Panel>

                <Panel title="Payment">
                    <div class="grid gap-4">
                        <Field
                            label="Payment condition"
                            for="cr-payment"
                            :error="form.errors.payment_rule"
                        >
                            <NativeSelect
                                id="cr-payment"
                                v-model="form.payment_rule"
                            >
                                <option
                                    v-for="rule in paymentRules"
                                    :key="rule.value"
                                    :value="rule.value"
                                >
                                    {{ rule.label }}
                                </option>
                            </NativeSelect>
                        </Field>
                        <template v-if="form.payment_rule !== 'none'">
                            <Field
                                label="Payment link"
                                for="cr-payment-url"
                                :error="form.errors.payment_url"
                                hint="Your own Stripe, PayPal or invoice link (https)."
                            >
                                <Input
                                    id="cr-payment-url"
                                    v-model="form.payment_url"
                                    type="url"
                                    placeholder="https://"
                                />
                            </Field>
                            <Field
                                label="Payment instructions"
                                for="cr-payment-instructions"
                                :error="form.errors.payment_instructions"
                            >
                                <TextArea
                                    id="cr-payment-instructions"
                                    v-model="form.payment_instructions"
                                    :rows="3"
                                    :maxlength="2000"
                                />
                            </Field>
                        </template>
                    </div>
                </Panel>

                <Panel title="Recipient">
                    <div class="grid gap-4">
                        <Field
                            label="Client name"
                            for="cr-recipient-name"
                            :error="form.errors.recipient_name"
                        >
                            <Input
                                id="cr-recipient-name"
                                v-model="form.recipient_name"
                                maxlength="120"
                            />
                        </Field>
                        <Field
                            label="Client email"
                            for="cr-recipient-email"
                            :error="form.errors.recipient_email"
                        >
                            <Input
                                id="cr-recipient-email"
                                v-model="form.recipient_email"
                                type="email"
                                maxlength="255"
                            />
                        </Field>
                        <p class="text-xs text-muted-foreground">
                            The approval link expires after
                            {{ defaults.expiry_days }} days.
                        </p>
                    </div>
                </Panel>

                <div class="grid gap-2">
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="save(true)"
                        >Save and send to client</Button
                    >
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="save(true, false)"
                        >Save and create link only</Button
                    >
                    <Button
                        type="submit"
                        variant="ghost"
                        :disabled="form.processing"
                        >Save draft</Button
                    >
                    <p class="text-xs text-muted-foreground">
                        Sending locks this revision. Later edits create a new
                        revision and a new link.
                    </p>
                </div>
            </div>
        </div>
    </form>
</template>
