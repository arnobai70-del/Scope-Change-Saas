<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentUrl } = useCurrentUrl();
const items = [
    { title: 'Overview', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: 'Workspaces', href: '/admin/workspaces' },
    { title: 'Webhooks', href: '/admin/webhooks' },
    { title: 'Support', href: '/admin/tickets' },
    { title: 'Feature flags', href: '/admin/flags' },
    { title: 'Admin audit', href: '/admin/audit' },
];
</script>

<template>
    <nav class="flex gap-1 overflow-x-auto border-b" aria-label="Admin">
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            :class="[
                '-mb-px border-b-2 px-3 py-2 text-sm whitespace-nowrap',
                isCurrentUrl(item.href)
                    ? 'border-primary font-medium'
                    : 'border-transparent text-muted-foreground hover:text-foreground',
            ]"
        >
            {{ item.title }}
        </Link>
    </nav>
</template>
