<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { BarChart3, Bot, ExternalLink, FileCode2, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminNav from '@/components/app/AdminNav.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'SEO & analytics', href: '/admin/seo' },
        ],
    },
});

type PageMeta = { title?: string | null; description?: string | null };

const props = defineProps<{
    settings: {
        meta: {
            default_description: string;
            og_image: string;
            google_verification: string;
            discourage_indexing: boolean;
            pages: Record<string, PageMeta>;
        };
        analytics: { ga_id: string };
        robots: { custom: string | null };
        sitemap: {
            include_segments: boolean;
            include_posts: boolean;
            extra_urls: string[];
            exclude: string[];
        };
    };
    pages: string[];
    defaultRobots: string;
    robotsPreview: string;
    sitemapCount: number;
    robotsUrl: string;
    sitemapUrl: string;
}>();

const sections = [
    { id: 'meta', title: 'Meta tags', icon: Search },
    { id: 'analytics', title: 'Google Analytics', icon: BarChart3 },
    { id: 'robots', title: 'robots.txt', icon: Bot },
    { id: 'sitemap', title: 'sitemap.xml', icon: FileCode2 },
];

const textarea =
    'w-full rounded-lg border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
const card = 'card-lift scroll-mt-6 rounded-2xl border bg-card p-5 sm:p-6';

// Meta tags
const meta = useForm({
    default_description: props.settings.meta.default_description,
    og_image: props.settings.meta.og_image,
    google_verification: props.settings.meta.google_verification,
    discourage_indexing: props.settings.meta.discourage_indexing,
    pages: Object.fromEntries(
        props.pages.map((path) => [
            path,
            {
                title: props.settings.meta.pages[path]?.title ?? '',
                description: props.settings.meta.pages[path]?.description ?? '',
            },
        ]),
    ) as Record<string, { title: string; description: string }>,
});
const pageFilter = ref('');
const visiblePages = computed(() =>
    props.pages.filter((p) => p.includes(pageFilter.value.trim())),
);
const metaErrors = computed(() => meta.errors as Record<string, string>);

// Analytics
const analytics = useForm({ ga_id: props.settings.analytics.ga_id });

// robots.txt
const robots = useForm({
    custom: props.settings.robots.custom ?? props.defaultRobots,
});

function saveRobots(): void {
    robots
        .transform((data) => ({
            custom:
                data.custom.trim() === props.defaultRobots.trim()
                    ? null
                    : data.custom,
        }))
        .put('/admin/seo/robots', { preserveScroll: true });
}

// sitemap.xml
const sitemap = useForm({
    include_segments: props.settings.sitemap.include_segments,
    include_posts: props.settings.sitemap.include_posts,
    extra: props.settings.sitemap.extra_urls.join('\n'),
    exclude: [...props.settings.sitemap.exclude],
});

function saveSitemap(): void {
    sitemap
        .transform((data) => ({
            include_segments: data.include_segments,
            include_posts: data.include_posts,
            extra_urls: data.extra
                .split('\n')
                .map((l) => l.trim())
                .filter(Boolean),
            exclude: data.exclude,
        }))
        .put('/admin/seo/sitemap', { preserveScroll: true });
}

function toggleExclude(path: string): void {
    sitemap.exclude = sitemap.exclude.includes(path)
        ? sitemap.exclude.filter((p) => p !== path)
        : [...sitemap.exclude, path];
}

const sitemapErrors = computed(() => sitemap.errors as Record<string, string>);
</script>

<template>
    <Head title="Admin · SEO & analytics" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            eyebrow="Admin control center"
            title="SEO & analytics"
            description="Control how the public website appears in search engines and how visits are measured."
        />
        <AdminNav />

        <nav class="flex flex-wrap gap-2" aria-label="SEO sections">
            <a
                v-for="s in sections"
                :key="s.id"
                :href="`#${s.id}`"
                class="group inline-flex items-center gap-2 rounded-full border bg-card px-3.5 py-1.5 text-sm font-medium transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 dark:hover:text-brand-300"
            >
                <component :is="s.icon" class="size-4" />
                {{ s.title }}
            </a>
        </nav>

        <!-- Meta tags -->
        <section id="meta" :class="card">
            <header class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300"
                    ><Search class="size-5"
                /></span>
                <div>
                    <h2 class="text-lg font-semibold">Meta tags</h2>
                    <p class="text-sm text-muted-foreground">
                        Site-wide defaults plus a custom meta title and
                        description for every public page. Leave a page blank to
                        keep its built-in text.
                    </p>
                </div>
            </header>
            <form
                class="mt-5 space-y-5"
                @submit.prevent="
                    meta.put('/admin/seo/meta', { preserveScroll: true })
                "
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="default_description"
                            >Default meta description</Label
                        >
                        <textarea
                            id="default_description"
                            v-model="meta.default_description"
                            rows="2"
                            maxlength="300"
                            :class="textarea"
                            placeholder="Used on pages that have no description of their own."
                        />
                        <InputError
                            :message="meta.errors.default_description"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="og_image">Social share image URL</Label>
                        <Input
                            id="og_image"
                            v-model="meta.og_image"
                            placeholder="https://example.com/og-image.png"
                        />
                        <InputError :message="meta.errors.og_image" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="google_verification"
                            >Google Search Console verification code</Label
                        >
                        <Input
                            id="google_verification"
                            v-model="meta.google_verification"
                            placeholder="The content value of the google-site-verification tag"
                        />
                        <InputError
                            :message="meta.errors.google_verification"
                        />
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-dashed p-3 text-sm transition hover:border-destructive/50"
                >
                    <input
                        v-model="meta.discourage_indexing"
                        type="checkbox"
                        class="mt-0.5 size-4 accent-[#e5484d]"
                    />
                    <span>
                        <span class="font-semibold"
                            >Hide the whole site from search engines</span
                        >
                        <span class="block text-muted-foreground"
                            >Adds noindex to every page and blocks all crawlers
                            in robots.txt. Use it only for staging sites.</span
                        >
                    </span>
                </label>

                <div class="rounded-xl border">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3"
                    >
                        <p class="text-sm font-semibold">
                            Per-page meta title and description
                        </p>
                        <Input
                            v-model="pageFilter"
                            class="h-8 w-48"
                            placeholder="Filter pages…"
                        />
                    </div>
                    <div class="max-h-[28rem] divide-y overflow-y-auto">
                        <div
                            v-for="path in visiblePages"
                            :key="path"
                            class="grid gap-2 px-4 py-3 lg:grid-cols-[12rem_1fr_1.4fr] lg:items-start"
                        >
                            <code
                                class="truncate pt-2 font-mono text-xs text-muted-foreground"
                                >{{ path }}</code
                            >
                            <div>
                                <Input
                                    v-model="meta.pages[path]!.title"
                                    maxlength="120"
                                    :aria-label="`Meta title for ${path}`"
                                    placeholder="Meta title"
                                />
                                <p
                                    class="mt-1 text-[11px] text-muted-foreground"
                                >
                                    {{ meta.pages[path]!.title.length }}/60
                                    recommended
                                </p>
                                <InputError
                                    :message="metaErrors[`pages.${path}.title`]"
                                />
                            </div>
                            <div>
                                <textarea
                                    v-model="meta.pages[path]!.description"
                                    rows="2"
                                    maxlength="300"
                                    :class="textarea"
                                    :aria-label="`Meta description for ${path}`"
                                    placeholder="Meta description"
                                />
                                <p class="text-[11px] text-muted-foreground">
                                    {{
                                        meta.pages[path]!.description.length
                                    }}/160 recommended
                                </p>
                                <InputError
                                    :message="
                                        metaErrors[`pages.${path}.description`]
                                    "
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    The site name is added after each title automatically.
                </p>
                <Button type="submit" :disabled="meta.processing"
                    >Save meta tags</Button
                >
            </form>
        </section>

        <!-- Google Analytics -->
        <section id="analytics" :class="card">
            <header class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-signal-soft text-lime-800"
                    ><BarChart3 class="size-5"
                /></span>
                <div>
                    <h2 class="text-lg font-semibold">Google Analytics</h2>
                    <p class="text-sm text-muted-foreground">
                        Paste your GA4 measurement ID to load Google Analytics
                        on the public website. Client approval pages and the app
                        are never tracked. Leave it empty to turn tracking off.
                    </p>
                </div>
            </header>
            <form
                class="mt-5 flex flex-wrap items-start gap-3"
                @submit.prevent="
                    analytics.put('/admin/seo/analytics', {
                        preserveScroll: true,
                    })
                "
            >
                <div class="grid w-full max-w-xs gap-2">
                    <Label for="ga_id">Measurement ID</Label>
                    <Input
                        id="ga_id"
                        v-model="analytics.ga_id"
                        placeholder="G-AB12CD34EF"
                    />
                    <InputError :message="analytics.errors.ga_id" />
                </div>
                <Button
                    type="submit"
                    class="mt-[1.375rem]"
                    :disabled="analytics.processing"
                    >Save</Button
                >
                <p
                    class="mt-1 w-full text-sm font-medium"
                    :class="
                        settings.analytics.ga_id
                            ? 'text-lime-700 dark:text-signal'
                            : 'text-muted-foreground'
                    "
                >
                    {{
                        settings.analytics.ga_id
                            ? `Tracking is on with ${settings.analytics.ga_id}.`
                            : 'Tracking is off.'
                    }}
                </p>
            </form>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <!-- robots.txt -->
            <section id="robots" :class="card">
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-ink text-signal"
                            ><Bot class="size-5"
                        /></span>
                        <div>
                            <h2 class="text-lg font-semibold">robots.txt</h2>
                            <p class="text-sm text-muted-foreground">
                                Tell crawlers which paths to skip.
                            </p>
                        </div>
                    </div>
                    <a
                        :href="robotsUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline dark:text-brand-300"
                        >Open <ExternalLink class="size-3.5"
                    /></a>
                </header>
                <form class="mt-5 space-y-3" @submit.prevent="saveRobots">
                    <textarea
                        v-model="robots.custom"
                        rows="9"
                        spellcheck="false"
                        :class="[textarea, 'font-mono text-xs']"
                        aria-label="robots.txt content"
                    />
                    <InputError :message="robots.errors.custom" />
                    <p
                        v-if="settings.meta.discourage_indexing"
                        class="text-xs font-medium text-destructive"
                    >
                        The whole site is hidden from search engines, so
                        robots.txt currently blocks everything.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="robots.processing"
                            >Save robots.txt</Button
                        >
                        <Button
                            type="button"
                            variant="outline"
                            @click="robots.custom = defaultRobots"
                            >Reset to default</Button
                        >
                    </div>
                </form>
            </section>

            <!-- sitemap.xml -->
            <section id="sitemap" :class="card">
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"
                            ><FileCode2 class="size-5"
                        /></span>
                        <div>
                            <h2 class="text-lg font-semibold">sitemap.xml</h2>
                            <p class="text-sm text-muted-foreground">
                                {{ sitemapCount }} URLs listed. Submit this link
                                in Google Search Console.
                            </p>
                        </div>
                    </div>
                    <a
                        :href="sitemapUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline dark:text-brand-300"
                        >Open <ExternalLink class="size-3.5"
                    /></a>
                </header>
                <form class="mt-5 space-y-4" @submit.prevent="saveSitemap">
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2">
                            <input
                                v-model="sitemap.include_segments"
                                type="checkbox"
                                class="size-4 accent-[#5b3df5]"
                            />
                            Include audience pages (/for/…)
                        </label>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="sitemap.include_posts"
                                type="checkbox"
                                class="size-4 accent-[#5b3df5]"
                            />
                            Include guides (/blog/…)
                        </label>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium">
                            Pages in the sitemap
                        </p>
                        <div
                            class="flex max-h-40 flex-wrap gap-1.5 overflow-y-auto"
                        >
                            <button
                                v-for="path in pages"
                                :key="path"
                                type="button"
                                :class="[
                                    'rounded-full border px-2.5 py-1 font-mono text-[11px] transition',
                                    sitemap.exclude.includes(path)
                                        ? 'border-dashed text-muted-foreground line-through'
                                        : 'border-brand-200 bg-brand-50 text-brand-800 hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-200',
                                ]"
                                :aria-pressed="!sitemap.exclude.includes(path)"
                                @click="toggleExclude(path)"
                            >
                                {{ path }}
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">
                            Click a page to remove it from or add it back to the
                            sitemap.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="extra">Extra URLs (one per line)</Label>
                        <textarea
                            id="extra"
                            v-model="sitemap.extra"
                            rows="3"
                            :class="[textarea, 'font-mono text-xs']"
                            placeholder="/landing/black-friday"
                        />
                        <InputError
                            :message="
                                Object.entries(sitemapErrors).find(([k]) =>
                                    k.startsWith('extra_urls'),
                                )?.[1]
                            "
                        />
                    </div>
                    <Button type="submit" :disabled="sitemap.processing"
                        >Save sitemap</Button
                    >
                </form>
            </section>
        </div>
    </div>
</template>
