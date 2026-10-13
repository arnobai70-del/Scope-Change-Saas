<script setup lang="ts">
defineProps<{ title: string; description?: string; eyebrow?: string }>();
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="mb-1 text-xs font-semibold tracking-[0.14em] text-primary uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="font-display text-2xl font-semibold tracking-tight sm:text-3xl"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
