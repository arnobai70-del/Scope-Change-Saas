<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Team', href: '/app/team' }] },
});

defineProps<{
    members: {
        id: number;
        name: string;
        email: string;
        role: string;
        is_owner: boolean;
    }[];
    invitations: {
        id: number;
        email: string;
        role: string;
        expires_at: string;
    }[];
    canManage: boolean;
    teamEnabled: boolean;
    seats: { used: number; limit: number | null };
}>();

const form = useForm({ email: '', role: 'member' });
const planError = computed(() => (form.errors as Record<string, string>).plan);

function invite(): void {
    form.post('/app/team/invitations', {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function changeRole(id: number, role: string): void {
    router.put(`/app/team/members/${id}`, { role }, { preserveScroll: true });
}

function remove(id: number, name: string): void {
    if (confirm(`Remove ${name} from this workspace?`)) {
        router.delete(`/app/team/members/${id}`, { preserveScroll: true });
    }
}

function cancel(id: number): void {
    router.delete(`/app/team/invitations/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Team" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Team"
            :description="`${seats.used} of ${seats.limit ?? 'unlimited'} seats used`"
        />

        <div
            v-if="!teamEnabled"
            class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-100"
        >
            Team seats are available on the Agency plan.
            <Link href="/app/billing" class="font-medium underline"
                >See plans</Link
            >
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Members" class="lg:col-span-2">
                <ul class="divide-y">
                    <li
                        v-for="m in members"
                        :key="m.id"
                        class="flex flex-wrap items-center gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ m.name }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ m.email }}
                            </p>
                        </div>
                        <span
                            v-if="m.is_owner || !canManage"
                            class="text-sm text-muted-foreground capitalize"
                            >{{ m.role }}</span
                        >
                        <template v-else>
                            <NativeSelect
                                class="w-32"
                                :model-value="m.role"
                                :aria-label="`Role for ${m.name}`"
                                @update:model-value="
                                    (v) => changeRole(m.id, String(v))
                                "
                            >
                                <option value="admin">Admin</option>
                                <option value="member">Member</option>
                            </NativeSelect>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive"
                                @click="remove(m.id, m.name)"
                                >Remove</Button
                            >
                        </template>
                    </li>
                </ul>
            </Panel>

            <div class="space-y-6">
                <Panel v-if="canManage" title="Invite a teammate">
                    <form class="grid gap-3" @submit.prevent="invite">
                        <p v-if="planError" class="text-sm text-destructive">
                            {{ planError }}
                        </p>
                        <Field
                            label="Email"
                            for="invite-email"
                            :error="form.errors.email"
                            ><Input
                                id="invite-email"
                                v-model="form.email"
                                type="email"
                                required
                        /></Field>
                        <Field
                            label="Role"
                            for="invite-role"
                            :error="form.errors.role"
                            hint="Admins can manage clients, settings and the team. Members manage projects and requests."
                        >
                            <NativeSelect id="invite-role" v-model="form.role">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </NativeSelect>
                        </Field>
                        <Button
                            type="submit"
                            :disabled="form.processing || !teamEnabled"
                            >Send invitation</Button
                        >
                    </form>
                </Panel>

                <Panel v-if="invitations.length" title="Pending invitations">
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="i in invitations"
                            :key="i.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <span class="min-w-0 truncate"
                                >{{ i.email }}
                                <span class="text-xs text-muted-foreground"
                                    >· {{ i.role }} · until
                                    {{ i.expires_at }}</span
                                ></span
                            >
                            <Button
                                v-if="canManage"
                                variant="ghost"
                                size="sm"
                                @click="cancel(i.id)"
                                >Cancel</Button
                            >
                        </li>
                    </ul>
                </Panel>
            </div>
        </div>
    </div>
</template>
