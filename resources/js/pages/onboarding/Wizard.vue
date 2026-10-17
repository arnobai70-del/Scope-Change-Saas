<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    workspace: {
        name: string;
        currency: string;
        timezone: string;
        service_type: string | null;
    };
    currencies: string[];
    timezones: string[];
}>();

const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

const form = useForm({
    name: props.workspace.name,
    currency: props.workspace.currency,
    timezone:
        props.workspace.timezone === 'UTC' &&
        props.timezones.includes(browserTimezone)
            ? browserTimezone
            : props.workspace.timezone,
    service_type: props.workspace.service_type ?? '',
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
    form.post('/app/onboarding');
}
</script>

<template>
    <Head title="Welcome" />
    <div class="relative min-h-screen overflow-hidden">
        <div
            class="bg-grid pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_70%)]"
            aria-hidden="true"
        />
        <main
            class="relative mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10"
        >
            <div
                class="mb-6 flex items-center gap-2 font-display font-semibold"
            >
                <AppLogoIcon class="size-8" />
                {{ $page.props.name }}
            </div>
            <div class="card-lift rounded-3xl border bg-card p-6 sm:p-8">
                <h1 class="font-display text-3xl font-semibold tracking-tight">
                    Set up your workspace
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    This is what clients see on change requests. You can change
                    it later.
                </p>

                <form class="mt-6 grid gap-5" @submit.prevent="submit">
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
                            <option
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                            >
                                {{ tz }}
                            </option>
                        </NativeSelect>
                    </Field>
                    <Field
                        label="What do you do?"
                        for="service_type"
                        :error="form.errors.service_type"
                    >
                        <NativeSelect
                            id="service_type"
                            v-model="form.service_type"
                        >
                            <option value="">Choose one</option>
                            <option
                                v-for="s in serviceTypes"
                                :key="s"
                                :value="s"
                            >
                                {{ s }}
                            </option>
                        </NativeSelect>
                    </Field>
                    <Button type="submit" :disabled="form.processing"
                        >Finish setup</Button
                    >
                </form>
            </div>
        </main>
    </div>
</template>
