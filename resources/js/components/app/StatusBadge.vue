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
            return 'bg-signal-soft text-lime-800 ring-lime-300 dark:bg-lime-950 dark:text-lime-200 dark:ring-lime-900';
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
            return 'bg-brand-50 text-brand-800 ring-brand-200 dark:bg-brand-950 dark:text-brand-200 dark:ring-brand-900';
        default:
            return 'bg-muted text-muted-foreground ring-border';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap ring-1 ring-inset',
            tone,
        ]"
        ><span
            class="size-1.5 shrink-0 rounded-full bg-current opacity-70"
            aria-hidden="true"
        />{{ label }}</span
    >
</template>
