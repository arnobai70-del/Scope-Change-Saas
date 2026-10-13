<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-dashed border-primary/30 bg-card p-10 text-center"
    >
        <div
            class="bg-grid pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black,transparent_70%)] opacity-70"
            aria-hidden="true"
        />
        <div class="relative">
            <div
                class="mx-auto mb-4 flex size-12 rotate-[-6deg] items-center justify-center rounded-2xl bg-signal text-ink shadow-sm"
                aria-hidden="true"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </div>
            <p class="font-display text-lg font-semibold">{{ title }}</p>
            <p
                v-if="description"
                class="mx-auto mt-1 max-w-md text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
            <div class="mt-5 flex justify-center gap-2">
                <slot />
            </div>
        </div>
    </div>
</template>
