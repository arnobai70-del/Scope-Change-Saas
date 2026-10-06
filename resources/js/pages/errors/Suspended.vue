<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ reason: string | null }>();
</script>

<template>
    <Head title="Workspace suspended" />
    <main
        class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 text-center"
    >
        <h1 class="text-2xl font-semibold">This workspace is suspended</h1>
        <p class="mt-3 text-muted-foreground">
            {{ reason || 'Access to this workspace has been paused.' }} Client
            approval links keep showing past decisions.
        </p>
        <p class="mt-6 text-sm">
            <Link href="/contact" class="underline">Contact support</Link>
            <span class="mx-2 text-muted-foreground">·</span>
            <Link href="/settings/profile" class="underline"
                >Account settings</Link
            >
        </p>
    </main>
</template>
