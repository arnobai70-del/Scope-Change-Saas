<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AdminNav from '@/components/app/AdminNav.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import TextArea from '@/components/app/TextArea.vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Support', href: '/admin/tickets' },
        ],
    },
});

type Ticket = {
    id: number;
    name: string;
    email: string;
    subject: string;
    body: string;
    state: string;
    priority: string;
    internal_notes: string | null;
    created_at: string | null;
};

const props = defineProps<{
    tickets: Paginated<Ticket>;
    filters: { state: string };
}>();

const drafts = reactive<
    Record<number, { state: string; priority: string; internal_notes: string }>
>(
    Object.fromEntries(
        props.tickets.data.map((t) => [
            t.id,
            {
                state: t.state,
                priority: t.priority,
                internal_notes: t.internal_notes ?? '',
            },
        ]),
    ),
);

function save(id: number): void {
    router.put(`/admin/tickets/${id}`, drafts[id], { preserveScroll: true });
}
</script>

<template>
    <Head title="Admin · Support" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader title="Support tickets" />
        <AdminNav />
        <div class="flex gap-1">
            <Button
                v-for="s in ['open', 'pending', 'resolved', 'all']"
                :key="s"
                size="sm"
                :variant="filters.state === s ? 'secondary' : 'ghost'"
                class="capitalize"
                @click="router.get('/admin/tickets', { state: s })"
                >{{ s }}</Button
            >
        </div>
        <p
            v-if="tickets.data.length === 0"
            class="text-sm text-muted-foreground"
        >
            No tickets.
        </p>
        <div
            v-for="t in tickets.data"
            :key="t.id"
            class="rounded-xl border p-4"
        >
            <p class="font-medium">{{ t.subject }}</p>
            <p class="text-xs text-muted-foreground">
                {{ t.name }} &lt;{{ t.email }}&gt; · {{ t.created_at }}
            </p>
            <p class="mt-2 text-sm whitespace-pre-line">{{ t.body }}</p>
            <div
                v-if="drafts[t.id]"
                class="mt-4 grid gap-2 sm:grid-cols-[10rem_10rem_1fr_auto] sm:items-start"
            >
                <NativeSelect v-model="drafts[t.id].state" aria-label="State"
                    ><option
                        v-for="s in ['open', 'pending', 'resolved']"
                        :key="s"
                        :value="s"
                    >
                        {{ s }}
                    </option></NativeSelect
                >
                <NativeSelect
                    v-model="drafts[t.id].priority"
                    aria-label="Priority"
                    ><option
                        v-for="p in ['low', 'normal', 'high', 'urgent']"
                        :key="p"
                        :value="p"
                    >
                        {{ p }}
                    </option></NativeSelect
                >
                <TextArea
                    v-model="drafts[t.id].internal_notes"
                    :rows="2"
                    aria-label="Internal notes"
                    placeholder="Internal notes"
                />
                <Button size="sm" @click="save(t.id)">Save</Button>
            </div>
        </div>
        <Pagination
            :prev="tickets.prev_page_url"
            :next="tickets.next_page_url"
            :page="tickets.current_page"
            :last="tickets.last_page"
        />
    </div>
</template>
