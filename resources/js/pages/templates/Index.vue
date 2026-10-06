<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/app/Field.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import TextArea from '@/components/app/TextArea.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type Template = {
    id: number;
    type: string;
    name: string;
    system: boolean;
    content: {
        title?: string | null;
        description?: string | null;
        scope_reason?: string | null;
        terms_note?: string | null;
    };
};

defineOptions({
    layout: { breadcrumbs: [{ title: 'Templates', href: '/app/templates' }] },
});

defineProps<{ templates: Template[] }>();

const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    type: 'change_request',
    name: '',
    content: { title: '', description: '', scope_reason: '', terms_note: '' },
});
const planError = computed(() => (form.errors as Record<string, string>).plan);

function start(template?: Template): void {
    form.clearErrors();
    editingId.value = template && !template.system ? template.id : null;
    form.name = template
        ? template.system
            ? `${template.name} (copy)`
            : template.name
        : '';
    form.content = {
        title: template?.content.title ?? '',
        description: template?.content.description ?? '',
        scope_reason: template?.content.scope_reason ?? '',
        terms_note: template?.content.terms_note ?? '',
    };
    open.value = true;
}

function save(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (editingId.value) {
        form.put(`/app/templates/${editingId.value}`, options);
    } else {
        form.post('/app/templates', options);
    }
}

function remove(template: Template): void {
    if (confirm(`Delete template "${template.name}"?`)) {
        router.delete(`/app/templates/${template.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Templates" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Templates"
            description="Reusable wording for common change requests. System templates are free on every plan."
        >
            <Button @click="start()"
                ><Plus class="size-4" /> New template</Button
            >
        </PageHeader>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="template in templates"
                :key="template.id"
                class="flex flex-col rounded-xl border p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium">{{ template.name }}</p>
                    <span
                        class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                        >{{ template.system ? 'System' : 'Yours' }}</span
                    >
                </div>
                <p
                    class="mt-2 line-clamp-3 flex-1 text-sm text-muted-foreground"
                >
                    {{ template.content.description }}
                </p>
                <div class="mt-4 flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="start(template)"
                        >{{ template.system ? 'Duplicate' : 'Edit' }}</Button
                    >
                    <Button
                        v-if="!template.system"
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="remove(template)"
                        >Delete</Button
                    >
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    editingId ? 'Edit template' : 'New template'
                }}</DialogTitle>
                <DialogDescription
                    >Applied to a new change request, it fills these fields. You
                    can still edit everything.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="save">
                <p
                    v-if="planError"
                    class="rounded-md bg-indigo-50 p-3 text-sm text-indigo-900"
                >
                    {{ planError }}
                </p>
                <Field
                    label="Template name"
                    for="tpl-name"
                    :error="form.errors.name"
                    ><Input
                        id="tpl-name"
                        v-model="form.name"
                        required
                        maxlength="120"
                /></Field>
                <Field label="Title" for="tpl-title"
                    ><Input
                        id="tpl-title"
                        v-model="form.content.title"
                        maxlength="160"
                /></Field>
                <Field label="Description" for="tpl-description"
                    ><TextArea
                        id="tpl-description"
                        v-model="form.content.description"
                        :rows="4"
                /></Field>
                <Field label="Why it is outside scope" for="tpl-reason"
                    ><TextArea
                        id="tpl-reason"
                        v-model="form.content.scope_reason"
                        :rows="2"
                /></Field>
                <Field label="Terms note" for="tpl-terms"
                    ><TextArea
                        id="tpl-terms"
                        v-model="form.content.terms_note"
                        :rows="2"
                /></Field>
                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing"
                        >Save template</Button
                    >
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
