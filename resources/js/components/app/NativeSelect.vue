<script setup lang="ts">
const model = defineModel<string | number | null>();

defineProps<{ id?: string; required?: boolean; invalid?: boolean }>();
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :required="required"
        :aria-invalid="invalid || undefined"
        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30 [&>option]:bg-background"
    >
        <slot />
    </select>
</template>
