<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    prev: string | null;
    next: string | null;
    page: number;
    last: number;
}>();
</script>

<template>
    <nav
        v-if="last > 1"
        class="flex items-center justify-between pt-4 text-sm"
        aria-label="Pagination"
    >
        <Link
            v-if="prev"
            :href="prev"
            preserve-scroll
            class="rounded-md border px-3 py-1.5 hover:bg-muted"
            >Previous</Link
        >
        <span v-else />
        <span class="text-muted-foreground">Page {{ page }} of {{ last }}</span>
        <Link
            v-if="next"
            :href="next"
            preserve-scroll
            class="rounded-md border px-3 py-1.5 hover:bg-muted"
            >Next</Link
        >
        <span v-else />
    </nav>
</template>
