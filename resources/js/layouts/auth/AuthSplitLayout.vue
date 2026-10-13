<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1fr_1.05fr]">
        <aside
            class="relative hidden overflow-hidden bg-ink p-10 text-white lg:flex lg:flex-col"
        >
            <div
                class="bg-grid-light pointer-events-none absolute inset-0"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -top-32 -right-24 size-96 rounded-full bg-brand-600/40 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -bottom-32 -left-24 size-80 rounded-full bg-signal/20 blur-3xl"
                aria-hidden="true"
            />

            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-2.5 font-display text-lg font-semibold"
            >
                <AppLogoIcon class="size-8" />
                {{ name }}
            </Link>

            <div class="relative z-10 my-auto max-w-md py-12">
                <div
                    class="relative rotate-[-2deg] rounded-2xl bg-white p-5 text-ink shadow-2xl"
                    aria-hidden="true"
                >
                    <p class="text-xs font-semibold text-ink/50">
                        CR-0042 · Website redesign
                    </p>
                    <p class="mt-1 font-display text-lg font-semibold">
                        Add a blog section
                    </p>
                    <div class="mt-3 flex items-center gap-3 text-sm">
                        <span
                            class="rounded-lg bg-ink px-2.5 py-1 font-display font-semibold text-white"
                            >$1,200</span
                        >
                        <span class="text-ink/60">+4 days</span>
                    </div>
                    <span
                        class="stamp absolute top-4 -right-3 border-lime-600 bg-signal-soft text-lime-800"
                        >Approved</span
                    >
                </div>
                <h2
                    class="mt-10 font-display text-4xl leading-tight font-semibold tracking-tight"
                >
                    Every extra request,
                    <span class="text-signal">priced and approved</span>
                    before work starts.
                </h2>
                <p class="mt-4 text-white/65">
                    No-login client approvals, reminders and a Proof Pack for
                    every change.
                </p>
            </div>

            <ul
                class="relative z-10 flex flex-wrap gap-x-6 gap-y-2 text-xs font-medium text-white/50"
            >
                <li class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-signal" />
                    No-login client approvals
                </li>
                <li class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-signal" />
                    Timestamped audit trail
                </li>
                <li class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-signal" />
                    PDF Proof Packs
                </li>
            </ul>
        </aside>

        <main
            class="relative flex flex-col items-center justify-center px-6 py-12 sm:px-10"
        >
            <Link
                :href="home()"
                class="mb-10 flex items-center gap-2 font-display text-lg font-semibold lg:hidden"
            >
                <AppLogoIcon class="size-8" />
                {{ name }}
            </Link>
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <h1
                        v-if="title"
                        class="font-display text-3xl font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
