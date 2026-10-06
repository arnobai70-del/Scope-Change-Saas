<script setup lang="ts">
const model = defineModel<string | null>();

defineProps<{
    id?: string;
    rows?: number;
    placeholder?: string;
    maxlength?: number;
    required?: boolean;
    invalid?: boolean;
}>();
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :rows="rows ?? 4"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :required="required"
        :aria-invalid="invalid || undefined"
        class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30"
    />
</template>
