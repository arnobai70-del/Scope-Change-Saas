@extends('layouts.marketing', ['title' => 'Features', 'description' => 'Scope baselines, priced change requests, no-login client approvals, reminders, payment conditions, Proof Packs and reporting.'])

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'Features',
    'heading' => 'Built for one outcome: <span class="marker">priced and approved</span> before work starts.',
    'intro' => 'Everything in the product exists for one outcome: extra work gets priced and approved before anyone starts it.',
])

{{-- Quick jump chips --}}
<nav class="mx-auto mt-8 flex max-w-6xl flex-wrap gap-2 px-4" aria-label="Features on this page">
    @foreach(['baselines' => 'Scope baselines', 'requests' => 'Change requests', 'approval' => 'Client approval', 'reminders' => 'Reminders', 'proof' => 'Proof Packs', 'reporting' => 'Reporting', 'teams' => 'Teams'] as $id => $label)
        <a href="#{{ $id }}" class="rounded-full border border-ink/10 bg-white px-4 py-1.5 text-sm font-medium text-ink/70 transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700">{{ $label }}</a>
    @endforeach
</nav>

<div class="mx-auto mt-16 max-w-6xl space-y-24 px-4">
    {{-- 1. Scope baselines --}}
    <section id="baselines" class="grid scroll-mt-24 items-center gap-10 lg:grid-cols-2">
        <div>
            <p class="font-display text-sm font-bold text-brand-600">01</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Scope baselines</h2>
            <p class="mt-4 leading-relaxed text-ink/70">Record deliverables, exclusions, revision allowances, assumptions and client responsibilities for each project. Lock a baseline to freeze it with a content hash. Editing a locked baseline creates a new version instead of rewriting history.</p>
        </div>
        <div class="group hover-lift rounded-3xl border border-ink/10 bg-white p-6 card-lift" aria-hidden="true">
            <div class="flex items-center justify-between">
                <p class="font-display font-semibold">Website redesign · v2</p>
                <span class="flex items-center gap-1.5 rounded-full bg-ink px-3 py-1 text-xs font-semibold text-white"><span class="h-1.5 w-1.5 rounded-full bg-signal"></span>Locked</span>
            </div>
            <ul class="mt-5 space-y-2.5 text-sm">
                @foreach([['Deliverable', '5 static pages', 'bg-brand-50 text-brand-700'], ['Deliverable', 'Contact form', 'bg-brand-50 text-brand-700'], ['Exclusion', 'Blog and CMS', 'bg-red-50 text-red-700'], ['Revisions', '2 rounds included', 'bg-amber-50 text-amber-700'], ['Client provides', 'Copy and photos', 'bg-paper text-ink/70']] as [$type, $item, $tone])
                    <li class="flex items-center gap-3 rounded-xl border border-ink/5 px-3 py-2.5 transition group-hover:border-ink/10">
                        <span class="w-28 shrink-0 rounded-md px-2 py-0.5 text-center text-[11px] font-semibold {{ $tone }}">{{ $type }}</span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 truncate font-mono text-[11px] text-ink/40">sha256 · 9f2c…e41a</p>
        </div>
    </section>

    {{-- 2. Change requests --}}
    <section id="requests" class="grid scroll-mt-24 items-center gap-10 lg:grid-cols-2">
        <div class="lg:order-2">
            <p class="font-display text-sm font-bold text-brand-600">02</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Change requests</h2>
            <ul class="mt-5 space-y-3">
                @foreach([
                    "Paste the client's original message and link the request to the scope items it extends.",
                    'Price in 25 currencies, with correct decimals for each one.',
                    'State the timeline impact in days or with a new delivery date.',
                    'Choose a payment condition: none, before start, before handoff, or custom terms.',
                    'Start from built-in templates or your own.',
                ] as $item)
                    <li class="flex gap-3 text-ink/75"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-signal text-[11px] font-bold text-ink" aria-hidden="true">✓</span>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        <div class="hover-lift relative overflow-hidden rounded-3xl bg-brand-600 p-6 text-white lg:order-1" aria-hidden="true">
            <div class="bg-grid-light absolute inset-0"></div>
            <div class="relative space-y-3">
                <div class="rounded-2xl bg-white/10 p-4 text-sm backdrop-blur"><p class="text-xs text-white/60">Client message</p><p class="mt-1">"Can we also get a blog? Nothing fancy."</p></div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    @foreach(['USD', 'EUR', 'GBP', 'JPY', 'BDT', 'INR', '+19'] as $c)
                        <span class="rounded-full bg-white/15 px-3 py-1 transition hover:bg-white hover:text-brand-700">{{ $c }}</span>
                    @endforeach
                </div>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-xl bg-white p-3 text-ink"><p class="text-[11px] text-ink/50">Price</p><p class="font-display font-semibold">$1,200</p></div>
                    <div class="rounded-xl bg-white/15 p-3"><p class="text-[11px] text-white/60">Timeline</p><p class="font-semibold">+4 days</p></div>
                    <div class="rounded-xl bg-signal p-3 text-ink"><p class="text-[11px] text-ink/60">Payment</p><p class="font-semibold">Before start</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Client approval --}}
    <section id="approval" class="grid scroll-mt-24 items-center gap-10 lg:grid-cols-2">
        <div>
            <p class="font-display text-sm font-bold text-brand-600">03</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">No-login client approval</h2>
            <p class="mt-4 leading-relaxed text-ink/70">Your client receives a branded, mobile-friendly page through a secure, expiring link. They can approve, decline with a reason, or ask a question. Approvals record name, email, timestamp and the exact revision they saw.</p>
        </div>
        <div class="group flex justify-center" aria-hidden="true">
            <div class="relative w-64 rounded-[2.5rem] border-[10px] border-ink bg-white p-4 shadow-2xl transition duration-500 group-hover:-rotate-2 group-hover:-translate-y-1">
                <div class="mx-auto mb-4 h-1.5 w-16 rounded-full bg-ink/15"></div>
                <div class="flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">N</span><span class="text-xs font-semibold">Northwind Studio</span></div>
                <p class="mt-4 font-display text-lg font-semibold leading-tight">Add a blog section</p>
                <p class="mt-1 font-display text-2xl font-semibold">$1,200</p>
                <span class="mt-4 block rounded-xl bg-brand-600 py-2.5 text-center text-sm font-semibold text-white">Approve change</span>
                <span class="mt-2 block rounded-xl border border-ink/15 py-2 text-center text-xs font-semibold">Ask a question</span>
                <span class="mt-2 block text-center text-xs text-ink/50">Decline</span>
                <span class="stamp absolute -right-8 top-24 border-lime-600 bg-signal-soft text-lime-800">Approved</span>
            </div>
        </div>
    </section>

    {{-- 4. Reminders --}}
    <section id="reminders" class="scroll-mt-24 rounded-3xl bg-ink p-8 text-white sm:p-12">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="font-display text-sm font-bold text-signal">04</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Reminders and notifications</h2>
                <p class="mt-4 leading-relaxed text-white/70">Automatic reminders go out at 24 hours, 72 hours and 24 hours before expiry, and stop the moment the client decides. You are notified when the client opens, approves, declines or asks a question.</p>
            </div>
            <ol class="relative space-y-5 border-l-2 border-white/15 pl-6" aria-label="Reminder timeline">
                @foreach([['Sent', 'Client gets the secure link', 'bg-white'], ['+24 hours', 'Friendly first reminder', 'bg-brand-400'], ['+72 hours', 'Second reminder', 'bg-brand-400'], ['24 h before expiry', 'Last call', 'bg-amber-400'], ['Decision', 'Reminders stop automatically', 'bg-signal']] as [$when, $what, $dot])
                    <li class="group relative">
                        <span class="absolute -left-[33px] top-1 h-4 w-4 rounded-full border-4 border-ink {{ $dot }} transition-transform group-hover:scale-125" aria-hidden="true"></span>
                        <p class="text-sm font-semibold">{{ $when }}</p>
                        <p class="text-sm text-white/60">{{ $what }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5 + 6. Proof Packs and Reporting --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <section id="proof" class="group hover-lift scroll-mt-24 rounded-3xl border border-ink/10 bg-white p-8">
            <p class="font-display text-sm font-bold text-brand-600">05</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Proof Packs</h2>
            <p class="mt-3 leading-relaxed text-ink/70">Generate one PDF per change request with the baseline, every locked revision, the client decision, payment status, conversation and a hash-chained activity log.</p>
            <div class="relative mt-8 h-36" aria-hidden="true">
                @foreach([['left-0', 'rotate-[-8deg] group-hover:rotate-[-12deg]', 'bg-paper'], ['left-10', 'rotate-[-2deg] group-hover:translate-x-2', 'bg-brand-50'], ['left-20', 'rotate-[5deg] group-hover:translate-x-6 group-hover:rotate-[9deg]', 'bg-white']] as $i => [$pos, $rot, $bg])
                    <div class="absolute top-0 {{ $pos }} h-32 w-24 rounded-xl border border-ink/10 {{ $bg }} p-3 shadow-md transition duration-500 {{ $rot }}">
                        <div class="h-1.5 w-12 rounded bg-ink/20"></div><div class="mt-2 h-1.5 w-16 rounded bg-ink/10"></div><div class="mt-1.5 h-1.5 w-10 rounded bg-ink/10"></div>
                        @if($i === 2)<p class="mt-6 text-center font-display text-sm font-bold text-brand-700">PDF</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
        <section id="reporting" class="group hover-lift scroll-mt-24 rounded-3xl border border-lime-300 bg-signal-soft p-8">
            <p class="font-display text-sm font-bold text-lime-700">06</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Reporting</h2>
            <p class="mt-3 leading-relaxed text-ink/70">See approved change revenue by currency, approval rate, average time to decision, and the projects that generate the most extra requests.</p>
            <div class="mt-8 flex h-36 items-end gap-2" aria-hidden="true">
                @foreach([35, 50, 42, 66, 58, 80, 72, 100] as $h)
                    <span class="flex-1 origin-bottom rounded-t-lg transition-transform duration-300 group-hover:scale-y-105 {{ $loop->last ? 'bg-ink' : 'bg-lime-500/70' }}" style="height: {{ $h }}%"></span>
                @endforeach
            </div>
        </section>
    </div>

    {{-- 7. Teams --}}
    <section id="teams" class="scroll-mt-24 flex flex-col gap-8 rounded-3xl border border-ink/10 bg-white p-8 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-xl">
            <p class="font-display text-sm font-bold text-brand-600">07</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Teams</h2>
            <p class="mt-3 leading-relaxed text-ink/70">Invite teammates with owner, admin or member roles on the Agency plan. Every action is attributed in the audit trail.</p>
        </div>
        <div class="flex flex-wrap gap-3" aria-hidden="true">
            @foreach([['A', 'Owner', 'bg-ink text-white'], ['M', 'Admin', 'bg-brand-600 text-white'], ['S', 'Member', 'bg-signal text-ink'], ['R', 'Member', 'bg-paper text-ink']] as [$initial, $role, $tone])
                <div class="flex items-center gap-2 rounded-full border border-ink/10 bg-white py-1.5 pl-1.5 pr-4 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-sm font-semibold {{ $tone }}">{{ $initial }}</span>
                    <span class="text-sm font-medium">{{ $role }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
