<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';

// oxfmt-ignore
type Props = {
    passwordRules: string;
    adminLocked?: boolean;
} &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div
        v-if="adminLocked"
        class="relative mb-8 overflow-hidden rounded-2xl bg-ink p-5 text-white sm:p-6"
    >
        <div
            class="bg-grid-light pointer-events-none absolute inset-0"
            aria-hidden="true"
        />
        <div class="relative">
            <p
                class="text-xs font-semibold tracking-[0.14em] text-signal uppercase"
            >
                Admin panel locked
            </p>
            <h2 class="mt-1 text-xl font-semibold">
                Turn on two-factor authentication to open the admin panel
            </h2>
            <p class="mt-1 text-sm text-white/70">
                Users, workspaces, feature flags and SEO &amp; analytics (meta
                tags, Google Analytics, robots.txt, sitemap.xml) unlock as soon
                as 2FA is on.
            </p>
            <ol class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                <li
                    v-for="(step, i) in [
                        'Click Enable 2FA below',
                        'Scan the QR code with Google Authenticator',
                        'Type the 6-digit code to confirm',
                    ]"
                    :key="step"
                    class="rounded-xl border border-white/10 bg-white/5 p-3"
                >
                    <span class="font-display font-semibold text-signal"
                        >0{{ i + 1 }}</span
                    >
                    <span class="mt-1 block text-white/85">{{ step }}</span>
                </li>
            </ol>
            <a
                href="#two-factor"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-signal px-4 py-2 text-sm font-semibold text-ink transition hover:-translate-y-0.5"
                >Go to Enable 2FA</a
            >
        </div>
    </div>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Save
                </Button>
            </div>
        </Form>
    </div>

    <div id="two-factor" class="scroll-mt-6" />
    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />
</template>
