@extends('layouts.marketing', ['title' => 'Turn scope creep into approved change revenue', 'description' => 'Create a priced change request in minutes, send a no-login approval link, and keep an audit trail and PDF Proof Pack for every extra request.'])

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden">
    <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)]" aria-hidden="true"></div>
    <div class="absolute -left-40 top-10 h-80 w-80 rounded-full bg-brand-300/40 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -right-32 top-48 h-72 w-72 rounded-full bg-signal/40 blur-3xl" aria-hidden="true"></div>

    <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-4 pb-20 pt-14 sm:pt-20 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
            <p class="eyebrow"><span class="h-1.5 w-1.5 rounded-full bg-lime-500"></span>For freelancers, agencies and consultants</p>
            <h1 class="mt-5 text-5xl font-semibold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl">
                Turn scope creep into <span class="marker">approved revenue</span>.
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink/70">Compare each extra request with the agreed scope, send a priced change request your client approves in one click, and keep timestamped proof of every decision.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="group btn-primary px-5 py-3 text-base">
                    Start free, no card needed
                    <svg viewBox="0 0 20 20" class="nudge h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg>
                </a>
                <a href="{{ route('marketing.examples.client-approval') }}" class="btn-outline px-5 py-3 text-base">See the client view</a>
            </div>
            <dl class="mt-10 grid max-w-lg grid-cols-3 gap-6 border-t border-ink/10 pt-6">
                <div><dt class="text-xs text-ink/55">Client account needed</dt><dd class="mt-1 font-display text-2xl font-semibold">None</dd></div>
                <div><dt class="text-xs text-ink/55">Currencies</dt><dd class="mt-1 font-display text-2xl font-semibold">25</dd></div>
                <div><dt class="text-xs text-ink/55">To send a request</dt><dd class="mt-1 font-display text-2xl font-semibold">~2 min</dd></div>
            </dl>
        </div>

        {{-- Product mock: the client message becomes an approved, priced change. --}}
        <div class="group relative mx-auto w-full max-w-md" aria-hidden="true">
            <div class="relative z-10 -mb-5 -ml-6 w-fit max-w-[15rem] rotate-[-3deg] rounded-2xl rounded-bl-sm border border-ink/10 bg-white px-4 py-3 text-sm card-lift">
                <p class="text-xs font-semibold text-ink/50">Client · 10:42</p>
                <p class="mt-1">"Could you just add a blog section too? Should be quick 🙏"</p>
            </div>
            <div class="relative rounded-3xl border border-ink/10 bg-white p-6 pt-9 card-lift transition duration-500 group-hover:-translate-y-1 group-hover:rotate-[0.6deg]">
                <div class="flex items-center justify-between text-xs">
                    <span class="rounded-full bg-paper px-2.5 py-1 font-semibold text-ink/60">CR-0042 · Revision 1</span>
                </div>
                <p class="mt-4 font-display text-2xl font-semibold leading-tight">Add a blog section with 3 article templates</p>
                <div class="mt-4 rounded-xl border border-dashed border-ink/15 bg-paper p-3 text-xs text-ink/65">
                    <span class="font-semibold text-ink">Outside agreed scope:</span> the baseline covers 5 static pages. A blog was listed as an exclusion.
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-xl bg-ink p-3 text-white"><p class="text-[11px] text-white/60">Price</p><p class="mt-0.5 font-display text-lg font-semibold">$1,200</p></div>
                    <div class="rounded-xl bg-paper p-3"><p class="text-[11px] text-ink/55">Timeline</p><p class="mt-0.5 font-semibold">+4 days</p></div>
                    <div class="rounded-xl bg-paper p-3"><p class="text-[11px] text-ink/55">Payment</p><p class="mt-0.5 font-semibold">Before start</p></div>
                </div>
                <div class="mt-5 flex gap-2">
                    <span class="flex-1 rounded-lg bg-brand-600 py-2.5 text-center text-sm font-semibold text-white">Approve change</span>
                    <span class="rounded-lg border border-ink/15 px-4 py-2.5 text-sm font-semibold">Ask</span>
                </div>
                <span class="stamp absolute -right-3 top-6 border-lime-600 bg-signal-soft text-lime-800">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10.5l4 4 8-9"/></svg>
                    Approved
                </span>
            </div>
            <div class="absolute -bottom-6 -right-4 flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-xs font-semibold text-white card-lift">
                <span class="h-2 w-2 animate-pulse rounded-full bg-signal"></span>
                Proof Pack ready · SHA-256 sealed
            </div>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="mx-auto max-w-6xl px-4 pt-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h2 class="max-w-xl text-3xl font-semibold tracking-tight sm:text-4xl">Three steps from "could you just…" to a signed-off change.</h2>
        <a href="{{ route('marketing.how-it-works') }}" class="group text-sm font-semibold text-brand-700"><span class="link-grow">How it works in detail</span> <span class="nudge inline-block">→</span></a>
    </div>
    <ol class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach([
            ['Lock the agreed scope', 'Record deliverables, exclusions, revision allowance and assumptions once. Lock it so both sides share one version of the truth.', 'bg-white'],
            ['Price the extra request', 'Paste the client message, link it to the original scope, add price, timeline impact and a payment condition.', 'bg-white'],
            ['Get a clear approval', 'Your client opens a branded page, no login needed, and approves, declines or asks a question. You get notified instantly.', 'bg-ink text-white'],
        ] as $i => [$heading, $text, $tone])
            <li class="hover-lift relative overflow-hidden rounded-3xl border border-ink/10 p-7 {{ $tone }}">
                <span class="font-display text-6xl font-bold leading-none {{ $i === 2 ? 'text-signal' : 'text-brand-600/15' }}">0{{ $i + 1 }}</span>
                <h3 class="mt-6 text-xl font-semibold">{{ $heading }}</h3>
                <p class="mt-2 text-sm leading-relaxed {{ $i === 2 ? 'text-white/70' : 'text-ink/65' }}">{{ $text }}</p>
            </li>
        @endforeach
    </ol>
</section>

{{-- Features bento --}}
<section class="mx-auto mt-24 max-w-6xl px-4">
    <p class="eyebrow"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>What's inside</p>
    <h2 class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">Everything you need to <span class="marker">stop working for free</span>.</h2>
    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
        <div class="hover-lift relative overflow-hidden rounded-3xl bg-brand-600 p-7 text-white sm:col-span-2 lg:col-span-4">
            <div class="bg-grid-light absolute inset-0" aria-hidden="true"></div>
            <div class="relative">
                <h3 class="text-2xl font-semibold">Immutable revisions</h3>
                <p class="mt-2 max-w-md text-white/75">Once sent, a request is locked. Any change creates a new revision, so approvals always match exactly what the client saw.</p>
                <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold" aria-hidden="true">
                    <span class="rounded-full bg-white/15 px-3 py-1.5 line-through decoration-white/60">Rev 1 · $900</span>
                    <span class="rounded-full bg-white/15 px-3 py-1.5">Rev 2 · $1,200</span>
                    <span class="rounded-full bg-signal px-3 py-1.5 text-ink">Approved ✓</span>
                </div>
            </div>
        </div>
        @foreach([
            ['Automatic reminders', 'Polite nudges after 24 and 72 hours and before expiry. They stop the moment your client decides.', 'lg:col-span-2'],
            ['Payment before work', 'Optionally require payment before work starts, using your own payment link. We never touch client funds.', 'lg:col-span-2'],
            ['Proof Packs', 'One PDF with the baseline, revisions, approval, payment status and a hash-chained activity log.', 'lg:col-span-2'],
            ['Multi-currency', '25 currencies with correct decimals, so a ¥ request never looks like a $ one.', 'lg:col-span-2'],
        ] as [$heading, $text, $span])
            <div class="hover-lift rounded-3xl border border-ink/10 bg-white p-7 hover:border-brand-300 {{ $span }}">
                <h3 class="text-lg font-semibold">{{ $heading }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink/65">{{ $text }}</p>
            </div>
        @endforeach
        <div class="group hover-lift rounded-3xl border border-lime-300 bg-signal-soft p-7 sm:col-span-2 lg:col-span-6 lg:flex lg:items-center lg:justify-between lg:gap-10">
            <div>
                <h3 class="text-lg font-semibold">Revenue reporting</h3>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-ink/70">See approved change revenue, approval rate and time to decision across every project.</p>
            </div>
            <div class="mt-5 flex items-end gap-1.5 lg:mt-0" aria-hidden="true">
                @foreach([30, 45, 38, 60, 52, 74, 68, 90] as $h)
                    <span class="w-6 origin-bottom rounded-t-md transition-transform duration-300 group-hover:scale-y-110 {{ $loop->last ? 'bg-ink' : 'bg-lime-500/70' }}" style="height: {{ $h }}px"></span>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Pricing --}}
<section class="mx-auto mt-24 max-w-6xl px-4">
    <div class="text-center">
        <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Simple pricing</h2>
        <p class="mt-3 text-ink/65">One approved change request usually pays for a year.</p>
    </div>
    <div class="mt-10">@include('marketing.partials.plans')</div>
</section>

{{-- FAQ --}}
<section class="mx-auto mt-24 max-w-3xl px-4">
    <h2 class="text-center text-3xl font-semibold tracking-tight">Questions</h2>
    <div class="mt-8 space-y-3">
        @foreach([
            ['Does my client need an account?', 'No. They receive a secure link and can approve on any phone or laptop. The link expires and can be revoked at any time.'],
            ['Is an approval legally binding?', 'We record who approved what, when, and the exact terms they saw. Whether that forms a binding agreement depends on your contract and jurisdiction. This is not legal advice.'],
            ['Do you process my client payments?', 'No. You add your own payment link (Stripe, PayPal, bank details). We only record whether payment was marked sent and confirmed.'],
            ['Can I cancel anytime?', 'Yes. Cancel from billing settings and keep access until the end of the paid period. Your data stays exportable.'],
        ] as [$q, $a])
            <details class="group rounded-2xl border border-ink/10 bg-white px-5 py-4 transition hover:border-brand-300 hover:shadow-md open:card-lift">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold [&::-webkit-details-marker]:hidden">
                    {{ $q }}
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-paper text-ink/60 transition group-hover:bg-brand-100 group-hover:text-brand-700 group-open:rotate-45 group-open:bg-signal group-open:text-ink" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3 text-sm leading-relaxed text-ink/65">{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>
@endsection
