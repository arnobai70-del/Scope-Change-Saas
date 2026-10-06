<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Pagination from '@/components/app/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Users', href: '/admin/users' },
        ],
    },
});

type Row = {
    id: number;
    name: string;
    email: string;
    verified: boolean;
    two_factor: boolean;
    status: string;
    is_admin: boolean;
    last_login_at: string | null;
    created_at: string | null;
};

const props = defineProps<{ users: Paginated<Row>; filters: { q: string } }>();
const q = ref(props.filters.q);

function setStatus(user: Row, status: 'active' | 'suspended'): void {
    const reason = prompt(
        `Reason for ${status === 'suspended' ? 'suspending' : 'restoring'} ${user.email}:`,
    );

    if (reason) {
        router.put(
            `/admin/users/${user.id}/status`,
            { status, reason },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head title="Admin · Users" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader title="Users" />
        <AdminNav />
        <form
            @submit.prevent="
                router.get(
                    '/admin/users',
                    { q: q || undefined },
                    { preserveState: true },
                )
            "
        >
            <Input
                v-model="q"
                type="search"
                placeholder="Search email or name"
                aria-label="Search users"
                class="max-w-sm"
            />
        </form>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-3 py-2">User</th>
                        <th class="px-3 py-2">Security</th>
                        <th class="px-3 py-2">Last login</th>
                        <th class="px-3 py-2">Status</th>
                        <th />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="u in users.data" :key="u.id">
                        <td class="px-3 py-2">
                            {{ u.name
                            }}<span
                                v-if="u.is_admin"
                                class="ml-1 text-xs text-primary"
                                >admin</span
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ u.email }} · joined {{ u.created_at }}
                            </p>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            {{ u.verified ? 'Verified' : 'Unverified' }} ·
                            {{ u.two_factor ? '2FA' : 'No 2FA' }}
                        </td>
                        <td class="px-3 py-2 text-xs">
                            {{ u.last_login_at ?? '—' }}
                        </td>
                        <td class="px-3 py-2 capitalize">{{ u.status }}</td>
                        <td class="px-3 py-2 text-right">
                            <Button
                                v-if="u.status === 'active'"
                                size="sm"
                                variant="ghost"
                                class="text-destructive"
                                @click="setStatus(u, 'suspended')"
                                >Suspend</Button
                            >
                            <Button
                                v-else
                                size="sm"
                                variant="ghost"
                                @click="setStatus(u, 'active')"
                                >Restore</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination
            :prev="users.prev_page_url"
            :next="users.next_page_url"
            :page="users.current_page"
            :last="users.last_page"
        />
    </div>
</template>
