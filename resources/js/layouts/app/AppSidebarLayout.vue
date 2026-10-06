<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import UpgradeNotice from '@/components/app/UpgradeNotice.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const workspace = computed(() => page.props.workspace);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div
                v-if="workspace?.on_trial"
                class="mx-4 mt-2 rounded-lg bg-muted px-4 py-2 text-sm"
            >
                You are on a free trial of the
                <span class="capitalize">{{ workspace.plan }}</span> plan until
                {{ workspace.trial_ends_at }}.
                <Link
                    v-if="workspace.is_owner"
                    href="/app/billing"
                    class="font-medium underline"
                    >Choose a plan</Link
                >
            </div>
            <div class="px-4 pt-2 empty:hidden"><UpgradeNotice /></div>
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
