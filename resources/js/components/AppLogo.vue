<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <AppLogoIcon class="size-8 shrink-0" />
    <span class="ml-1 truncate font-display text-base font-semibold">{{
        name
    }}</span>
</template>
