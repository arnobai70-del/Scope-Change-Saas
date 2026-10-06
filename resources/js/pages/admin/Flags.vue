<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Feature flags', href: '/admin/flags' },
        ],
    },
});

defineProps<{
    flags: {
        key: string;
        enabled: boolean;
        overridden: boolean;
        description: string | null;
    }[];
}>();

function toggle(key: string, enabled: boolean): void {
    router.put('/admin/flags', { key, enabled }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Admin · Feature flags" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Feature flags"
            description="Overrides stored in the database take precedence over config defaults."
        />
        <AdminNav />
        <ul class="divide-y rounded-xl border">
            <li
                v-for="flag in flags"
                :key="flag.key"
                class="flex items-center justify-between gap-3 px-4 py-3"
            >
                <div>
                    <p class="font-mono text-sm">{{ flag.key }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ flag.overridden ? 'Overridden' : 'Config default'
                        }}<span v-if="flag.description">
                            · {{ flag.description }}</span
                        >
                    </p>
                </div>
                <Button
                    size="sm"
                    :variant="flag.enabled ? 'default' : 'outline'"
                    @click="toggle(flag.key, !flag.enabled)"
                    >{{ flag.enabled ? 'On' : 'Off' }}</Button
                >
            </li>
        </ul>
    </div>
</template>
