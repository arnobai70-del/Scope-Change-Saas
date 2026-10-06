<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

const page = usePage();
const workspace = computed(() => page.props.workspace);

function switchTo(id: number): void {
    if (id !== workspace.value?.id) {
        router.post(`/app/workspaces/${id}/switch`);
    }
}
</script>

<template>
    <SidebarMenu v-if="workspace">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent"
                    >
                        <div
                            class="flex aspect-square size-8 items-center justify-center rounded-md text-sm font-semibold text-white"
                            :style="{
                                background: workspace.brand_color || '#4f46e5',
                            }"
                        >
                            {{ workspace.name.slice(0, 1).toUpperCase() }}
                        </div>
                        <div
                            class="grid flex-1 text-left text-sm leading-tight"
                        >
                            <span class="truncate font-semibold">{{
                                workspace.name
                            }}</span>
                            <span
                                class="truncate text-xs text-muted-foreground capitalize"
                                >{{ workspace.plan }} plan{{
                                    workspace.on_trial ? ' (trial)' : ''
                                }}</span
                            >
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="min-w-56" align="start">
                    <DropdownMenuLabel class="text-xs text-muted-foreground"
                        >Workspaces</DropdownMenuLabel
                    >
                    <DropdownMenuItem
                        v-for="item in workspace.all"
                        :key="item.id"
                        @click="switchTo(item.id)"
                    >
                        <span class="flex-1 truncate">{{ item.name }}</span>
                        <Check v-if="item.id === workspace.id" class="size-4" />
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
