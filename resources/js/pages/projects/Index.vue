<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import ProjectForm from '@/components/app/ProjectForm.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Option, Paginated, ProjectSummary } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Projects', href: '/app/projects' }] },
});

const props = defineProps<{
    projects: Paginated<ProjectSummary>;
    filters: { status: string };
    clients: Option[];
    currencies: string[];
    defaultCurrency: string;
    openCreate: boolean;
    presetClientId: number | null;
}>();

const creating = ref(props.openCreate && props.clients.length > 0);
const tabs = [
    { value: 'open', label: 'Open' },
    { value: 'completed', label: 'Completed' },
    { value: 'archived', label: 'Archived' },
    { value: 'all', label: 'All' },
];

function filter(status: string): void {
    router.get(
        '/app/projects',
        { status },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Projects" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Projects"
            description="Each project holds the agreed scope your change requests are compared against."
        >
            <Button v-if="clients.length" @click="creating = true"
                ><Plus class="size-4" /> New project</Button
            >
            <Button v-else as-child
                ><Link href="/app/clients?new=1"
                    >Add a client first</Link
                ></Button
            >
        </PageHeader>

        <div class="flex gap-1 overflow-x-auto" role="tablist">
            <Button
                v-for="tab in tabs"
                :key="tab.value"
                size="sm"
                :variant="filters.status === tab.value ? 'secondary' : 'ghost'"
                role="tab"
                :aria-selected="filters.status === tab.value"
                @click="filter(tab.value)"
            >
                {{ tab.label }}
            </Button>
        </div>

        <EmptyState
            v-if="projects.data.length === 0"
            title="No projects here"
            description="Create a project, record what you agreed, then send change requests against it."
        >
            <Button v-if="clients.length" @click="creating = true"
                >Create a project</Button
            >
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Project</th>
                        <th class="hidden px-4 py-2 font-medium md:table-cell">
                            Client
                        </th>
                        <th class="hidden px-4 py-2 font-medium sm:table-cell">
                            Fee
                        </th>
                        <th class="px-4 py-2 font-medium">Changes</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="project in projects.data"
                        :key="project.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="`/app/projects/${project.id}`"
                                class="font-medium hover:underline"
                                >{{ project.title }}</Link
                            >
                            <p
                                v-if="project.code"
                                class="text-xs text-muted-foreground"
                            >
                                {{ project.code }}
                            </p>
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            {{ project.client?.name }}
                        </td>
                        <td class="hidden px-4 py-3 sm:table-cell">
                            {{ project.base_amount?.formatted ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ project.change_requests_count }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="project.status"
                                :label="project.status_label"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="projects.prev_page_url"
            :next="projects.next_page_url"
            :page="projects.current_page"
            :last="projects.last_page"
        />
    </div>

    <Dialog v-model:open="creating">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>New project</DialogTitle>
                <DialogDescription
                    >You will add the agreed scope on the next
                    screen.</DialogDescription
                >
            </DialogHeader>
            <ProjectForm
                :clients="clients"
                :currencies="currencies"
                :default-currency="defaultCurrency"
                :preset-client-id="presetClientId"
                @saved="creating = false"
            />
        </DialogContent>
    </Dialog>
</template>
