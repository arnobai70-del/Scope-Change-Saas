<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const message = computed(() => {
    const errors = (page.props.errors ?? {}) as Record<string, string>;

    return (
        errors.plan ??
        (page.props.flash?.upgrade ? 'This needs a higher plan.' : null)
    );
});
</script>

<template>
    <div
        v-if="message"
        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-100"
        role="status"
    >
        <span>{{ message }}</span>
        <Link href="/app/billing" class="font-medium underline">See plans</Link>
    </div>
</template>
