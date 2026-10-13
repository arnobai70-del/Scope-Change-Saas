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

            <div class="relative z-10 mx-auto my-auto w-full max-w-lg py-10">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-white/75"
                >
                    <span class="relative flex size-2">
                        <span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-signal opacity-60"
                        />
                        <span
                            class="relative inline-flex size-2 rounded-full bg-signal"
                        />
                    </span>
                    Built for freelancers and agencies
                </span>

                <div class="relative mt-10 mr-6 ml-2" aria-hidden="true">
                    <div
                        class="absolute inset-0 translate-x-5 translate-y-5 rotate-[3deg] rounded-2xl border border-white/10 bg-white/5"
                    />
                    <div
                        class="relative rotate-[-2deg] rounded-2xl bg-white p-5 text-ink shadow-2xl"
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
                    <div
                        class="float-slow absolute -top-7 right-10 flex items-center gap-2 rounded-xl bg-ink-soft px-3 py-2 text-xs font-medium shadow-xl ring-1 ring-white/10"
                    >
                        <span
                            class="flex size-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold"
                            >A</span
                        >
                        Acme Co. opened the request
                    </div>
                    <div
                        class="float-slow-delay absolute -bottom-9 left-8 flex items-center gap-2 rounded-xl bg-signal px-3 py-2 text-xs font-semibold text-ink shadow-xl"
                    >
                        <svg
                            viewBox="0 0 16 16"
                            class="size-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 8.5l3 3 7-7" />
                        </svg>
                        Proof Pack ready to download
                    </div>
                </div>

                <h2
                    class="mt-16 font-display text-4xl leading-tight font-semibold tracking-tight"
                >
                    Every extra request,
                    <span class="text-signal">priced and approved</span>
                    before work starts.
                </h2>
                <p class="mt-4 text-white/65">
                    No-login client approvals, reminders and a Proof Pack for
                    every change.
                </p>

                <ol class="mt-8 grid grid-cols-3 gap-2 text-xs">
                    <li
                        v-for="(step, i) in [
                            'Agree the scope',
                            'Price the change',
                            'Client approves',
                        ]"
                        :key="step"
                        class="rounded-xl border border-white/10 bg-white/5 p-3 transition duration-200 hover:-translate-y-0.5 hover:border-signal/40 hover:bg-white/10"
                    >
                        <span
                            class="font-display text-lg font-semibold text-signal"
                            >0{{ i + 1 }}</span
                        >
                        <span class="mt-1 block font-medium text-white/80">{{
                            step
                        }}</span>
                    </li>
                </ol>
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
