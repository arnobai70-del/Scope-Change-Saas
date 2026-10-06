<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Option, ProjectSummary } from '@/types';

const props = defineProps<{
    project?: ProjectSummary | null;
    clients: Option[];
    currencies: string[];
    defaultCurrency: string;
    presetClientId?: number | null;
}>();
const emit = defineEmits<{ saved: [] }>();

const form = useForm({
    client_id:
        props.project?.client?.id ??
        props.presetClientId ??
        props.clients[0]?.id ??
        null,
    title: props.project?.title ?? '',
    code: props.project?.code ?? '',
    base_amount: props.project?.base_amount?.decimal ?? '',
    currency: props.project?.currency ?? props.defaultCurrency,
    status:
        props.project?.status && props.project.status !== 'archived'
            ? props.project.status
            : 'active',
    revision_allowance: (props.project?.revision_allowance ?? '') as
        | number
        | string,
    starts_on: props.project?.starts_on ?? '',
    ends_on: props.project?.ends_on ?? '',
});

function submit(): void {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };

    if (props.project) {
        form.put(`/app/projects/${props.project.id}`, options);
    } else {
        form.post('/app/projects', options);
    }
}
</script>

<template>
    <form class="grid gap-4" @submit.prevent="submit">
        <Field
            label="Client"
            for="project-client"
            :error="form.errors.client_id"
        >
            <NativeSelect id="project-client" v-model="form.client_id" required>
                <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.id"
                >
                    {{ client.name }}
                </option>
            </NativeSelect>
        </Field>
        <Field
            label="Project title"
            for="project-title"
            :error="form.errors.title"
        >
            <Input
                id="project-title"
                v-model="form.title"
                required
                maxlength="160"
                placeholder="e.g. Website redesign"
            />
        </Field>
        <div class="grid gap-4 sm:grid-cols-2">
            <Field
                label="Agreed fee"
                for="project-amount"
                :error="form.errors.base_amount"
                hint="Optional, for your reporting."
            >
                <Input
                    id="project-amount"
                    v-model="form.base_amount"
                    inputmode="decimal"
                    placeholder="0.00"
                />
            </Field>
            <Field
                label="Currency"
                for="project-currency"
                :error="form.errors.currency"
            >
                <NativeSelect id="project-currency" v-model="form.currency">
                    <option v-for="c in currencies" :key="c" :value="c">
                        {{ c }}
                    </option>
                </NativeSelect>
            </Field>
            <Field
                label="Reference code"
                for="project-code"
                :error="form.errors.code"
            >
                <Input
                    id="project-code"
                    v-model="form.code"
                    maxlength="40"
                    placeholder="Optional"
                />
            </Field>
            <Field
                label="Revision rounds included"
                for="project-revisions"
                :error="form.errors.revision_allowance"
            >
                <Input
                    id="project-revisions"
                    v-model="form.revision_allowance"
                    type="number"
                    min="0"
                    max="100"
                />
            </Field>
            <Field
                label="Start date"
                for="project-start"
                :error="form.errors.starts_on"
            >
                <Input
                    id="project-start"
                    v-model="form.starts_on"
                    type="date"
                />
            </Field>
            <Field
                label="Delivery date"
                for="project-end"
                :error="form.errors.ends_on"
            >
                <Input id="project-end" v-model="form.ends_on" type="date" />
            </Field>
        </div>
        <Field
            v-if="project"
            label="Status"
            for="project-status"
            :error="form.errors.status"
        >
            <NativeSelect id="project-status" v-model="form.status">
                <option value="active">Active</option>
                <option value="on_hold">On hold</option>
                <option value="completed">Completed</option>
            </NativeSelect>
        </Field>
        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">{{
                project ? 'Save changes' : 'Create project'
            }}</Button>
        </div>
    </form>
</template>
