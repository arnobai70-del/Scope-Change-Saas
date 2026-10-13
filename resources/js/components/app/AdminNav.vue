<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    Flag,
    LayoutDashboard,
    LifeBuoy,
    ScrollText,
    Search,
    Users,
    Webhook,
} from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentUrl } = useCurrentUrl();
const items = [
    { title: 'Overview', href: '/admin', icon: LayoutDashboard },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Workspaces', href: '/admin/workspaces', icon: Building2 },
    { title: 'Webhooks', href: '/admin/webhooks', icon: Webhook },
    { title: 'Support', href: '/admin/tickets', icon: LifeBuoy },
    { title: 'Feature flags', href: '/admin/flags', icon: Flag },
    { title: 'SEO & analytics', href: '/admin/seo', icon: Search },
    { title: 'Admin audit', href: '/admin/audit', icon: ScrollText },
];
</script>

<template>
    <nav
        class="flex gap-1 overflow-x-auto rounded-2xl bg-ink p-1.5 dark:bg-muted"
        aria-label="Admin"
    >
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            :class="[
                'group flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium whitespace-nowrap transition-all duration-200',
                isCurrentUrl(item.href)
                    ? 'bg-signal text-ink shadow-sm'
                    : 'text-white/70 hover:bg-white/10 hover:text-white',
            ]"
        >
            <component
                :is="item.icon"
                class="size-4 transition-transform duration-200 group-hover:scale-110"
            />
            {{ item.title }}
        </Link>
    </nav>
</template>
