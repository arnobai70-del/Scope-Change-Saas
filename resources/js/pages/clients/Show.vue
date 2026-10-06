<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ClientForm from '@/components/app/ClientForm.vue';
import Field from '@/components/app/Field.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { ClientSummary, ProjectSummary } from '@/types';

const props = defineProps<{
    client: ClientSummary;
    contacts: {
        id: number;
        name: string;
        email: string;
        role: string | null;
        is_primary: boolean;
    }[];
    projects: ProjectSummary[];
}>();

defineOptions({
    layout: (props: { client: ClientSummary }) => ({
        breadcrumbs: [
            { title: 'Clients', href: '/app/clients' },
            {
                title: props.client.display_name,
                href: `/app/clients/${props.client.id}`,
            },
        ],
    }),
});

const editing = ref(false);
const contactForm = useForm({
    name: '',
    email: '',
    role: '',
    is_primary: false,
});

function addContact(): void {
    contactForm.post(`/app/clients/${props.client.id}/contacts`, {
        preserveScroll: true,
        onSuccess: () => contactForm.reset(),
    });
}

function removeContact(id: number): void {
    if (confirm('Remove this contact?')) {
        router.delete(`/app/clients/${props.client.id}/contacts/${id}`, {
            preserveScroll: true,
        });
    }
}

function toggleArchive(): void {
    router.post(
        `/app/clients/${props.client.id}/archive`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="client.display_name" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="client.display_name"
            :description="client.email ?? undefined"
        >
            <Button variant="outline" @click="editing = true">Edit</Button>
            <Button variant="outline" @click="toggleArchive">{{
                client.archived ? 'Restore' : 'Archive'
            }}</Button>
            <Button as-child
                ><Link :href="`/app/projects?new=1&client_id=${client.id}`"
                    ><Plus class="size-4" /> New project</Link
                ></Button
            >
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Projects" class="lg:col-span-2">
                <p
                    v-if="projects.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No projects for this client yet.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="`/app/projects/${project.id}`"
                                class="font-medium hover:underline"
                                >{{ project.title }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{
                                    project.base_amount?.formatted ??
                                    project.currency
                                }}
                            </p>
                        </div>
                        <StatusBadge
                            :status="project.status"
                            :label="project.status_label"
                        />
                    </li>
                </ul>
            </Panel>

            <div class="space-y-6">
                <Panel
                    title="Contacts"
                    description="The primary contact receives change requests by default."
                >
                    <ul class="mb-4 divide-y text-sm">
                        <li
                            v-for="contact in contacts"
                            :key="contact.id"
                            class="flex items-center justify-between gap-2 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ contact.name }}
                                    <span
                                        v-if="contact.is_primary"
                                        class="text-xs text-muted-foreground"
                                        >(primary)</span
                                    >
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ contact.email
                                    }}<span v-if="contact.role">
                                        · {{ contact.role }}</span
                                    >
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Remove ${contact.name}`"
                                @click="removeContact(contact.id)"
                                ><Trash2 class="size-4"
                            /></Button>
                        </li>
                    </ul>
                    <form class="grid gap-3" @submit.prevent="addContact">
                        <Field
                            label="Name"
                            for="contact-name"
                            :error="contactForm.errors.name"
                            ><Input
                                id="contact-name"
                                v-model="contactForm.name"
                                required
                        /></Field>
                        <Field
                            label="Email"
                            for="contact-email"
                            :error="contactForm.errors.email"
                            ><Input
                                id="contact-email"
                                v-model="contactForm.email"
                                type="email"
                                required
                        /></Field>
                        <Field
                            label="Role"
                            for="contact-role"
                            :error="contactForm.errors.role"
                            ><Input
                                id="contact-role"
                                v-model="contactForm.role"
                                placeholder="e.g. Marketing lead"
                        /></Field>
                        <label class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="contactForm.is_primary"
                                type="checkbox"
                                class="size-4"
                            />
                            Make primary</label
                        >
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="contactForm.processing"
                            >Add contact</Button
                        >
                    </form>
                </Panel>

                <Panel v-if="client.notes" title="Notes">
                    <p class="text-sm whitespace-pre-line">
                        {{ client.notes }}
                    </p>
                </Panel>
            </div>
        </div>
    </div>

    <Dialog v-model:open="editing">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader><DialogTitle>Edit client</DialogTitle></DialogHeader>
            <ClientForm :client="client" @saved="editing = false" />
        </DialogContent>
    </Dialog>
</template>
