<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import TextArea from '@/components/app/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    workspace: {
        name: string;
        currency: string;
        timezone: string;
        service_type: string | null;
        default_payment_url: string | null;
        default_payment_instructions: string | null;
    };
    currencies: string[];
    timezones: string[];
}>();

const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const step = ref(1);

const form = useForm({
    name: props.workspace.name,
    currency: props.workspace.currency,
    timezone:
        props.workspace.timezone === 'UTC' &&
        props.timezones.includes(browserTimezone)
            ? browserTimezone
            : props.workspace.timezone,
    service_type: props.workspace.service_type ?? '',
    default_payment_url: props.workspace.default_payment_url ?? '',
    default_payment_instructions:
        props.workspace.default_payment_instructions ?? '',
});

const serviceTypes = [
    'Web design',
    'Development',
    'Branding',
    'Marketing',
    'Video & content',
    'Consulting',
    'Other',
];

function submit(): void {
    form.post('/app/onboarding', {
        onError: () => {
            step.value =
                form.errors.default_payment_url ||
                form.errors.default_payment_instructions
                    ? 2
                    : 1;
        },
    });
}
</script>

<template>
    <Head title="Welcome" />
    <main
        class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10"
    >
        <p class="text-sm text-muted-foreground">Step {{ step }} of 2</p>
        <h1 class="mt-1 text-2xl font-semibold">
            {{ step === 1 ? 'Set up your workspace' : 'How clients pay you' }}
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">
            {{
                step === 1
                    ? 'This is what clients see on change requests. You can change it later.'
                    : 'Optional. We prefill this on change requests that need payment. We never handle client money.'
            }}
        </p>

        <form
            class="mt-6 grid gap-5"
            @submit.prevent="step === 1 ? (step = 2) : submit()"
        >
            <template v-if="step === 1">
                <Field
                    label="Business or studio name"
                    for="name"
                    :error="form.errors.name"
                >
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        maxlength="120"
                        autocomplete="organization"
                    />
                </Field>
                <Field
                    label="Main currency"
                    for="currency"
                    :error="form.errors.currency"
                >
                    <NativeSelect id="currency" v-model="form.currency">
                        <option v-for="c in currencies" :key="c" :value="c">
                            {{ c }}
                        </option>
                    </NativeSelect>
                </Field>
                <Field
                    label="Timezone"
                    for="timezone"
                    :error="form.errors.timezone"
                    hint="Used for deadlines, reminders and timestamps."
                >
                    <NativeSelect id="timezone" v-model="form.timezone">
                        <option v-for="tz in timezones" :key="tz" :value="tz">
                            {{ tz }}
                        </option>
                    </NativeSelect>
                </Field>
                <Field
                    label="What do you do?"
                    for="service_type"
                    :error="form.errors.service_type"
                >
                    <NativeSelect id="service_type" v-model="form.service_type">
                        <option value="">Choose one</option>
                        <option v-for="s in serviceTypes" :key="s" :value="s">
                            {{ s }}
                        </option>
                    </NativeSelect>
                </Field>
                <Button type="submit">Continue</Button>
            </template>

            <template v-else>
                <Field
                    label="Payment link"
                    for="default_payment_url"
                    :error="form.errors.default_payment_url"
                    hint="A Stripe, PayPal or invoice link. Must start with https://"
                >
                    <Input
                        id="default_payment_url"
                        v-model="form.default_payment_url"
                        type="url"
                        placeholder="https://"
                    />
                </Field>
                <Field
                    label="Payment instructions"
                    for="default_payment_instructions"
                    :error="form.errors.default_payment_instructions"
                >
                    <TextArea
                        id="default_payment_instructions"
                        v-model="form.default_payment_instructions"
                        :rows="3"
                        placeholder="Bank transfer details or other instructions"
                    />
                </Field>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="step = 1"
                        >Back</Button
                    >
                    <Button type="submit" :disabled="form.processing"
                        >Finish setup</Button
                    >
                </div>
            </template>
        </form>
    </main>
</template>
