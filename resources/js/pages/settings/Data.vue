<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Field from '@/components/app/Field.vue';
import TextArea from '@/components/app/TextArea.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Data & privacy', href: '/app/settings/data' }],
    },
});

defineProps<{
    exports: {
        id: number;
        type: string;
        status: string;
        created_at: string | null;
        download_url: string | null;
    }[];
    deletionRequested: boolean;
    canManage: boolean;
}>();

const deletion = useForm({ reason: '', confirm: false });

function exportData(type: 'json' | 'csv'): void {
    router.post(
        '/app/settings/data/export',
        { type },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Data & privacy" />
    <h1 class="sr-only">Data and privacy</h1>
    <div class="space-y-10">
        <section class="space-y-4">
            <Heading
                variant="small"
                title="Export your data"
                description="Download clients, projects, scope, change requests, decisions and activity."
            />
            <div v-if="canManage" class="flex gap-2">
                <Button variant="outline" @click="exportData('json')"
                    >Export JSON</Button
                >
                <Button variant="outline" @click="exportData('csv')"
                    >Export CSV</Button
                >
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Only owners and admins can export workspace data.
            </p>
            <ul
                v-if="exports.length"
                class="divide-y rounded-lg border text-sm"
            >
                <li
                    v-for="e in exports"
                    :key="e.id"
                    class="flex items-center justify-between px-3 py-2"
                >
                    <span
                        >{{ e.type.toUpperCase() }} · {{ e.created_at }} ·
                        <span class="text-muted-foreground">{{
                            e.status
                        }}</span></span
                    >
                    <a
                        v-if="e.download_url"
                        :href="e.download_url"
                        class="underline"
                        >Download</a
                    >
                </li>
            </ul>
        </section>

        <section class="space-y-4 border-t pt-6">
            <Heading
                variant="small"
                title="Delete workspace"
                description="Ask us to delete this workspace and its data. Records we must keep by law are retained only as long as required."
            />
            <p v-if="deletionRequested" class="rounded-md bg-muted p-3 text-sm">
                A deletion request is pending. We will confirm by email.
            </p>
            <form
                v-else
                class="space-y-3"
                @submit.prevent="
                    deletion.post('/app/settings/data/deletion', {
                        preserveScroll: true,
                    })
                "
            >
                <Field
                    label="Reason (optional)"
                    for="deletion-reason"
                    :error="deletion.errors.reason"
                    ><TextArea
                        id="deletion-reason"
                        v-model="deletion.reason"
                        :rows="2"
                /></Field>
                <label class="flex items-center gap-2 text-sm"
                    ><input
                        v-model="deletion.confirm"
                        type="checkbox"
                        class="size-4"
                    />
                    I understand this deletes all projects, change requests and
                    Proof Packs.</label
                >
                <InputError :message="deletion.errors.confirm" />
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="deletion.processing || !deletion.confirm"
                    >Request deletion</Button
                >
            </form>
        </section>
    </div>
</template>
