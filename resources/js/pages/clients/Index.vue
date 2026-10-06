<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import ClientForm from '@/components/app/ClientForm.vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { ClientSummary, Paginated } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Clients', href: '/app/clients' }] },
});

const props = defineProps<{
    clients: Paginated<ClientSummary>;
    filters: { archived: boolean; q: string };
    openCreate: boolean;
}>();

const creating = ref(props.openCreate);
const search = ref(props.filters.q);

function applyFilters(archived = props.filters.archived): void {
    router.get(
        '/app/clients',
        { q: search.value || undefined, archived: archived ? 1 : undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Clients" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Clients"
            description="The people and companies you send change requests to."
        >
            <Button @click="creating = true"
                ><Plus class="size-4" /> Add client</Button
            >
        </PageHeader>

        <div class="flex flex-wrap items-center gap-2">
            <form class="flex-1" @submit.prevent="applyFilters()">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name, company or email"
                    aria-label="Search clients"
                    class="max-w-sm"
                />
            </form>
            <Button
                variant="ghost"
                size="sm"
                @click="applyFilters(!filters.archived)"
            >
                {{ filters.archived ? 'Show active' : 'Show archived' }}
            </Button>
        </div>

        <EmptyState
            v-if="clients.data.length === 0"
            :title="
                filters.q || filters.archived
                    ? 'No clients match'
                    : 'No clients yet'
            "
            description="Add a client to create projects and send change requests."
        >
            <Button
                v-if="!filters.q && !filters.archived"
                @click="creating = true"
                >Add your first client</Button
            >
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Client</th>
                        <th class="hidden px-4 py-2 font-medium sm:table-cell">
                            Email
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            Projects
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="client in clients.data"
                        :key="client.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="`/app/clients/${client.id}`"
                                class="font-medium hover:underline"
                                >{{ client.display_name }}</Link
                            >
                            <p
                                v-if="client.company"
                                class="text-xs text-muted-foreground"
                            >
                                {{ client.name }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground sm:table-cell"
                        >
                            {{ client.email }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ client.projects_count }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="clients.prev_page_url"
            :next="clients.next_page_url"
            :page="clients.current_page"
            :last="clients.last_page"
        />
    </div>

    <Dialog v-model:open="creating">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Add client</DialogTitle>
                <DialogDescription
                    >Only you and your team see this
                    information.</DialogDescription
                >
            </DialogHeader>
            <ClientForm @saved="creating = false" />
        </DialogContent>
    </Dialog>
</template>
