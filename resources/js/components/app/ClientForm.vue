<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import TextArea from '@/components/app/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ClientSummary } from '@/types';

const props = defineProps<{ client?: ClientSummary | null }>();
const emit = defineEmits<{ saved: [] }>();

const form = useForm({
    type: props.client?.type ?? 'company',
    name: props.client?.name ?? '',
    company: props.client?.company ?? '',
    email: props.client?.email ?? '',
    phone: props.client?.phone ?? '',
    notes: props.client?.notes ?? '',
});

function submit(): void {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };

    if (props.client) {
        form.put(`/app/clients/${props.client.id}`, options);
    } else {
        form.post('/app/clients', options);
    }
}
</script>

<template>
    <form class="grid gap-4" @submit.prevent="submit">
        <Field label="Type" for="client-type" :error="form.errors.type">
            <NativeSelect id="client-type" v-model="form.type">
                <option value="company">Company</option>
                <option value="person">Individual</option>
            </NativeSelect>
        </Field>
        <Field
            :label="form.type === 'company' ? 'Contact name' : 'Name'"
            for="client-name"
            :error="form.errors.name"
        >
            <Input
                id="client-name"
                v-model="form.name"
                required
                maxlength="160"
            />
        </Field>
        <Field
            v-if="form.type === 'company'"
            label="Company"
            for="client-company"
            :error="form.errors.company"
        >
            <Input id="client-company" v-model="form.company" maxlength="160" />
        </Field>
        <Field
            label="Email"
            for="client-email"
            :error="form.errors.email"
            hint="Change requests are sent here by default."
        >
            <Input
                id="client-email"
                v-model="form.email"
                type="email"
                maxlength="255"
            />
        </Field>
        <Field label="Phone" for="client-phone" :error="form.errors.phone">
            <Input id="client-phone" v-model="form.phone" maxlength="40" />
        </Field>
        <Field
            label="Private notes"
            for="client-notes"
            :error="form.errors.notes"
        >
            <TextArea id="client-notes" v-model="form.notes" :rows="3" />
        </Field>
        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">{{
                client ? 'Save changes' : 'Add client'
            }}</Button>
        </div>
    </form>
</template>
