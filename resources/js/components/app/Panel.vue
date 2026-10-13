<script setup lang="ts">
defineProps<{ title?: string; description?: string }>();
</script>

<template>
    <section
        class="card-lift rounded-2xl border bg-card p-5 text-card-foreground transition-colors duration-300 hover:border-primary/20 sm:p-6"
    >
        <header
            v-if="title || $slots.actions"
            class="mb-4 flex flex-wrap items-start justify-between gap-2"
        >
            <div>
                <h2 v-if="title" class="font-display text-lg font-semibold">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-0.5 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <slot name="actions" />
        </header>
        <slot />
    </section>
</template>
