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
                class="mx-4 mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-ink px-4 py-2.5 text-sm text-white/80 dark:bg-muted"
            >
                <span
                    class="h-2 w-2 shrink-0 animate-pulse rounded-full bg-signal"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1">
                    Free trial of the
                    <span class="font-semibold text-white capitalize">{{
                        workspace.plan
                    }}</span>
                    plan until {{ workspace.trial_ends_at }}.
                </span>
                <Link
                    v-if="workspace.is_owner"
                    href="/app/billing"
                    class="rounded-lg bg-signal px-3 py-1 text-xs font-semibold text-ink hover:brightness-95"
                    >Choose a plan</Link
                >
            </div>
            <div class="px-4 pt-2 empty:hidden"><UpgradeNotice /></div>
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
