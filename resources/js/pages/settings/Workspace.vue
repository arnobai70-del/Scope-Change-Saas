<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import TextArea from '@/components/app/TextArea.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Workspace settings', href: '/app/settings/workspace' },
        ],
    },
});

const props = defineProps<{
    workspace: {
        name: string;
        currency: string;
        timezone: string;
        service_type: string | null;
        brand_color: string;
        default_expiry_days: number;
        reminders_enabled: boolean;
        default_payment_url: string | null;
        default_payment_instructions: string | null;
        logo_url: string | null;
    };
    currencies: string[];
    timezones: string[];
    canManage: boolean;
    remindersOnPlan: boolean;
}>();

const form = useForm({
    name: props.workspace.name,
    currency: props.workspace.currency,
    timezone: props.workspace.timezone,
    service_type: props.workspace.service_type ?? '',
    brand_color: props.workspace.brand_color || '#4f46e5',
    default_expiry_days: props.workspace.default_expiry_days,
    reminders_enabled: props.workspace.reminders_enabled,
    default_payment_url: props.workspace.default_payment_url ?? '',
    default_payment_instructions:
        props.workspace.default_payment_instructions ?? '',
});

const logoForm = useForm<{ logo: File | null }>({ logo: null });

function save(): void {
    form.put('/app/settings/workspace', { preserveScroll: true });
}

function uploadLogo(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (!file) {
        return;
    }

    logoForm.logo = file;
    logoForm.post('/app/settings/workspace/logo', {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => logoForm.reset(),
    });
}
</script>

<template>
    <Head title="Workspace settings" />
    <h1 class="sr-only">Workspace settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Workspace"
            description="Branding and defaults your clients see on change requests."
        />
        <p v-if="!canManage" class="rounded-md bg-muted p-3 text-sm">
            Only owners and admins can change workspace settings.
        </p>

        <form class="space-y-5" @submit.prevent="save">
            <fieldset :disabled="!canManage" class="space-y-5">
                <Field
                    label="Workspace name"
                    for="ws-name"
                    :error="form.errors.name"
                    ><Input
                        id="ws-name"
                        v-model="form.name"
                        required
                        maxlength="120"
                /></Field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Default currency"
                        for="ws-currency"
                        :error="form.errors.currency"
                    >
                        <NativeSelect id="ws-currency" v-model="form.currency"
                            ><option
                                v-for="c in currencies"
                                :key="c"
                                :value="c"
                            >
                                {{ c }}
                            </option></NativeSelect
                        >
                    </Field>
                    <Field
                        label="Timezone"
                        for="ws-timezone"
                        :error="form.errors.timezone"
                    >
                        <NativeSelect id="ws-timezone" v-model="form.timezone"
                            ><option
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                            >
                                {{ tz }}
                            </option></NativeSelect
                        >
                    </Field>
                    <Field
                        label="Brand colour"
                        for="ws-color"
                        :error="form.errors.brand_color"
                    >
                        <div class="flex gap-2">
                            <input
                                id="ws-color"
                                v-model="form.brand_color"
                                type="color"
                                class="h-9 w-12 rounded border"
                            /><Input
                                v-model="form.brand_color"
                                aria-label="Brand colour hex"
                                maxlength="7"
                            />
                        </div>
                    </Field>
                    <Field
                        label="Link expiry (days)"
                        for="ws-expiry"
                        :error="form.errors.default_expiry_days"
                    >
                        <Input
                            id="ws-expiry"
                            v-model="form.default_expiry_days"
                            type="number"
                            min="1"
                            max="90"
                            required
                        />
                    </Field>
                </div>
                <label class="flex items-start gap-2 text-sm">
                    <input
                        v-model="form.reminders_enabled"
                        type="checkbox"
                        class="mt-0.5 size-4"
                    />
                    <span
                        >Send automatic reminders to clients<span
                            v-if="!remindersOnPlan"
                            class="block text-xs text-muted-foreground"
                            >Reminders run on Solo plans and above.</span
                        ></span
                    >
                </label>
                <Field
                    label="Default payment link"
                    for="ws-pay-url"
                    :error="form.errors.default_payment_url"
                    ><Input
                        id="ws-pay-url"
                        v-model="form.default_payment_url"
                        type="url"
                        placeholder="https://"
                /></Field>
                <Field
                    label="Default payment instructions"
                    for="ws-pay-instructions"
                    :error="form.errors.default_payment_instructions"
                >
                    <TextArea
                        id="ws-pay-instructions"
                        v-model="form.default_payment_instructions"
                        :rows="3"
                    />
                </Field>
                <Button type="submit" :disabled="form.processing">Save</Button>
            </fieldset>
        </form>

        <div v-if="canManage" class="space-y-3 border-t pt-6">
            <Heading
                variant="small"
                title="Logo"
                description="PNG, JPG or WebP, up to 1 MB. Shown on your client approval pages."
            />
            <img
                v-if="workspace.logo_url"
                :src="workspace.logo_url"
                alt="Current logo"
                class="h-12 w-12 rounded object-contain"
            />
            <input
                type="file"
                accept="image/png,image/jpeg,image/webp"
                aria-label="Upload logo"
                class="text-sm"
                @change="uploadLogo"
            />
            <InputError :message="logoForm.errors.logo" />
        </div>
    </div>
</template>
