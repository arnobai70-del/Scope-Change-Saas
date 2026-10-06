<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string; label: string }>();

const tone = computed(() => {
    switch (props.status) {
        case 'approved':
        case 'ready_to_start':
        case 'completed':
        case 'proof_packed':
        case 'confirmed':
        case 'active':
            return 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-900';
        case 'declined':
        case 'expired':
        case 'revoked':
        case 'failed':
            return 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900';
        case 'questioned':
        case 'payment_pending':
        case 'payment_marked_sent':
        case 'marked_sent':
        case 'pending':
        case 'on_hold':
            return 'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-900';
        case 'sent':
        case 'viewed':
        case 'in_progress':
            return 'bg-indigo-50 text-indigo-800 ring-indigo-200 dark:bg-indigo-950 dark:text-indigo-200 dark:ring-indigo-900';
        default:
            return 'bg-neutral-100 text-neutral-700 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
            tone,
        ]"
        >{{ label }}</span
    >
</template>
