<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BarChart3,
    CreditCard,
    FileCheck2,
    FileStack,
    FolderKanban,
    LayoutGrid,
    ListChecks,
    Plus,
    Settings,
    Shield,
    Users,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.vue';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/app/dashboard', icon: LayoutGrid },
    {
        title: 'Change requests',
        href: '/app/change-requests',
        icon: ListChecks,
    },
    { title: 'Projects', href: '/app/projects', icon: FolderKanban },
    { title: 'Clients', href: '/app/clients', icon: UsersRound },
    { title: 'Proof Packs', href: '/app/proof-packs', icon: FileCheck2 },
    { title: 'Templates', href: '/app/templates', icon: FileStack },
    { title: 'Reports', href: '/app/reports', icon: BarChart3 },
];

const workspaceNavItems = computed(() => {
    const items: (NavItem & { badge?: number })[] = [
        {
            title: 'Notifications',
            href: '/app/notifications',
            icon: Bell,
            badge: page.props.unreadNotifications || undefined,
        },
        { title: 'Team', href: '/app/team', icon: Users },
        { title: 'Billing', href: '/app/billing', icon: CreditCard },
        { title: 'Settings', href: '/app/settings/workspace', icon: Settings },
    ];

    if (page.props.auth.user?.is_admin) {
        items.push({ title: 'Admin', href: '/admin', icon: Shield });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <Link
                href="/app/dashboard"
                class="flex items-center gap-2 px-2 pt-1 pb-2 font-display text-base font-semibold text-white group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
            >
                <AppLogoIcon class="size-7 shrink-0" />
                <span class="truncate group-data-[collapsible=icon]:hidden">{{
                    page.props.name
                }}</span>
            </Link>
            <WorkspaceSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="workspaceNavItems" label="Workspace" />
            <div
                class="mx-2 mt-auto mb-2 rounded-xl border border-sidebar-border bg-sidebar-accent/60 p-3 group-data-[collapsible=icon]:hidden"
            >
                <p class="text-xs font-semibold text-white">
                    Client asked for something extra?
                </p>
                <p
                    class="mt-1 text-[11px] leading-snug text-sidebar-foreground/80"
                >
                    Price it and send an approval link in a minute.
                </p>
                <Link
                    href="/app/change-requests/create"
                    class="mt-2.5 flex items-center justify-center gap-1.5 rounded-lg bg-signal px-3 py-1.5 text-xs font-semibold text-ink transition duration-200 hover:-translate-y-0.5 hover:brightness-95"
                >
                    <Plus class="size-3.5" />
                    New change request
                </Link>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
