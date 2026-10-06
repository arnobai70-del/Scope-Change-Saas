<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Lock, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import ProjectForm from '@/components/app/ProjectForm.vue';
import ScopeEditor from '@/components/app/ScopeEditor.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { moneyList } from '@/lib/format';
import type {
    Baseline,
    ChangeRequestRow,
    Money,
    Option,
    ProjectSummary,
} from '@/types';

const props = defineProps<{
    project: ProjectSummary;
    baseline: Baseline | null;
    lockedBaseline: Baseline | null;
    baselineVersions: {
        version: number;
        locked_at: string | null;
        content_hash: string | null;
    }[];
    changeRequests: ChangeRequestRow[];
    summary: {
        approved_totals: Money[];
        timeline_days: number;
        approved_count: number;
    };
    activity: { id: number; event: string; actor: string; at: string }[];
    clients: Option[];
    currencies: string[];
    tab: string;
}>();

defineOptions({
    layout: (props: { project: ProjectSummary }) => ({
        breadcrumbs: [
            { title: 'Projects', href: '/app/projects' },
            {
                title: props.project.title,
                href: `/app/projects/${props.project.id}`,
            },
        ],
    }),
});

const tabs = [
    { value: 'overview', label: 'Overview' },
    { value: 'scope', label: 'Scope' },
    { value: 'changes', label: 'Change requests' },
    { value: 'activity', label: 'Activity' },
];
const current = ref(
    tabs.some((t) => t.value === props.tab) ? props.tab : 'overview',
);
const editing = ref(false);
const editingScope = ref(props.baseline === null);
const grouped = computed(() => {
    const groups: Record<string, Baseline['items']> = {};

    for (const item of props.baseline?.items ?? []) {
        (groups[item.type_label ?? item.type] ??= []).push(item);
    }

    return groups;
});

function lockScope(): void {
    if (
        confirm(
            'Lock this scope? Locked scope cannot be edited. Later changes create a new version.',
        )
    ) {
        router.post(
            `/app/projects/${props.project.id}/scope/lock`,
            {},
            { preserveScroll: true },
        );
    }
}

function toggleArchive(): void {
    router.post(
        `/app/projects/${props.project.id}/archive`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="project.title" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader :title="project.title" :description="project.client?.name">
            <StatusBadge
                :status="project.status"
                :label="project.status_label"
            />
            <Button variant="outline" @click="editing = true">Edit</Button>
            <Button variant="outline" @click="toggleArchive">{{
                project.status === 'archived' ? 'Restore' : 'Archive'
            }}</Button>
            <Button as-child
                ><Link
                    :href="`/app/change-requests/create?project_id=${project.id}`"
                    ><Plus class="size-4" /> Change request</Link
                ></Button
            >
        </PageHeader>

        <div class="flex gap-1 overflow-x-auto border-b" role="tablist">
            <button
                v-for="t in tabs"
                :key="t.value"
                role="tab"
                :aria-selected="current === t.value"
                :class="[
                    '-mb-px border-b-2 px-3 py-2 text-sm whitespace-nowrap',
                    current === t.value
                        ? 'border-primary font-medium'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
                @click="current = t.value"
            >
                {{ t.label }}
            </button>
        </div>

        <div
            v-if="current === 'overview'"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Agreed fee</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ project.base_amount?.formatted ?? '—' }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Approved changes</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ moneyList(summary.approved_totals) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ summary.approved_count }} approved
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Timeline added</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ summary.timeline_days }} days
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Scope</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ baseline ? `v${baseline.version}` : 'Not set' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        baseline?.locked
                            ? `Locked ${baseline.locked_at}`
                            : baseline
                              ? 'Draft, not locked'
                              : 'Add it on the Scope tab'
                    }}
                </p>
            </div>
            <Panel title="Dates" class="sm:col-span-2">
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-muted-foreground">Start</dt>
                    <dd>{{ project.starts_on ?? '—' }}</dd>
                    <dt class="text-muted-foreground">Delivery</dt>
                    <dd>{{ project.ends_on ?? '—' }}</dd>
                    <dt class="text-muted-foreground">Revision rounds</dt>
                    <dd>{{ project.revision_allowance ?? '—' }}</dd>
                    <dt class="text-muted-foreground">Reference</dt>
                    <dd>{{ project.code ?? '—' }}</dd>
                </dl>
            </Panel>
            <Panel title="Latest change requests" class="sm:col-span-2">
                <p
                    v-if="changeRequests.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    None yet.
                </p>
                <ul v-else class="divide-y text-sm">
                    <li
                        v-for="cr in changeRequests.slice(0, 5)"
                        :key="cr.id"
                        class="flex items-center justify-between gap-2 py-2"
                    >
                        <Link
                            :href="`/app/change-requests/${cr.id}`"
                            class="truncate hover:underline"
                            >{{ cr.title }}</Link
                        >
                        <StatusBadge
                            :status="cr.status"
                            :label="cr.status_label"
                        />
                    </li>
                </ul>
            </Panel>
        </div>

        <div v-else-if="current === 'scope'" class="grid gap-6 lg:grid-cols-3">
            <Panel
                :title="
                    baseline
                        ? `${baseline.title} · v${baseline.version}`
                        : 'Agreed scope'
                "
                class="lg:col-span-2"
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button
                            v-if="baseline && !editingScope"
                            variant="outline"
                            size="sm"
                            @click="editingScope = true"
                            >{{
                                baseline.locked ? 'New version' : 'Edit'
                            }}</Button
                        >
                        <Button
                            v-if="baseline && !baseline.locked && !editingScope"
                            size="sm"
                            @click="lockScope"
                            ><Lock class="size-4" /> Lock scope</Button
                        >
                    </div>
                </template>
                <ScopeEditor
                    v-if="editingScope"
                    :project-id="project.id"
                    :baseline="baseline"
                    :project-title="project.title"
                    @saved="editingScope = false"
                />
                <template v-else-if="baseline">
                    <p
                        v-if="baseline.summary"
                        class="mb-4 text-sm text-muted-foreground"
                    >
                        {{ baseline.summary }}
                    </p>
                    <div
                        v-for="(items, label) in grouped"
                        :key="label"
                        class="mb-4"
                    >
                        <p class="text-sm font-medium">{{ label }}</p>
                        <ul class="mt-1 space-y-1 text-sm">
                            <li v-for="item in items" :key="item.id">
                                • {{ item.title }}
                                <span
                                    v-if="item.detail"
                                    class="text-muted-foreground"
                                    >— {{ item.detail }}</span
                                >
                            </li>
                        </ul>
                    </div>
                    <p
                        v-if="baseline.content_hash"
                        class="font-mono text-xs break-all text-muted-foreground"
                    >
                        SHA-256 {{ baseline.content_hash }}
                    </p>
                </template>
            </Panel>
            <Panel
                title="Versions"
                description="Change requests compare against the latest locked version."
            >
                <p
                    v-if="baselineVersions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No versions yet.
                </p>
                <ul class="space-y-2 text-sm">
                    <li v-for="v in baselineVersions" :key="v.version">
                        <span class="font-medium">v{{ v.version }}</span>
                        <span class="text-muted-foreground">
                            ·
                            {{
                                v.locked_at ? `locked ${v.locked_at}` : 'draft'
                            }}</span
                        >
                    </li>
                </ul>
                <p
                    v-if="lockedBaseline"
                    class="mt-4 text-xs text-muted-foreground"
                >
                    Locked v{{ lockedBaseline.version }} remains the reference
                    until the new version is locked.
                </p>
            </Panel>
        </div>

        <div v-else-if="current === 'changes'">
            <EmptyState
                v-if="changeRequests.length === 0"
                title="No change requests yet"
                description="When the client asks for something extra, price it here."
            >
                <Button as-child
                    ><Link
                        :href="`/app/change-requests/create?project_id=${project.id}`"
                        >Create change request</Link
                    ></Button
                >
            </EmptyState>
            <div v-else class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Request</th>
                            <th class="px-4 py-2 font-medium">Price</th>
                            <th
                                class="hidden px-4 py-2 font-medium sm:table-cell"
                            >
                                Sent
                            </th>
                            <th class="px-4 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="cr in changeRequests" :key="cr.id">
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/app/change-requests/${cr.id}`"
                                    class="font-medium hover:underline"
                                    >{{ cr.title }}</Link
                                >
                                <p class="text-xs text-muted-foreground">
                                    {{ cr.reference }} · rev
                                    {{ cr.revision_no }}
                                </p>
                            </td>
                            <td class="px-4 py-3">{{ cr.price?.formatted }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                {{ cr.sent_at ?? '—' }}
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
        </div>

        <Panel v-else title="Activity">
            <p
                v-if="activity.length === 0"
                class="text-sm text-muted-foreground"
            >
                No activity yet.
            </p>
            <ol class="space-y-3 text-sm">
                <li v-for="event in activity" :key="event.id">
                    <p>{{ event.event }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ event.actor }} · {{ event.at }}
                    </p>
                </li>
            </ol>
        </Panel>
    </div>

    <Dialog v-model:open="editing">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader><DialogTitle>Edit project</DialogTitle></DialogHeader>
            <ProjectForm
                :project="project"
                :clients="clients"
                :currencies="currencies"
                :default-currency="project.currency"
                @saved="editing = false"
            />
        </DialogContent>
    </Dialog>
</template>
