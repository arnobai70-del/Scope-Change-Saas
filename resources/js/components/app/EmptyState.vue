<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="rounded-xl border border-dashed p-10 text-center">
        <p class="font-medium">{{ title }}</p>
        <p
            v-if="description"
            class="mx-auto mt-1 max-w-md text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div class="mt-4 flex justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
