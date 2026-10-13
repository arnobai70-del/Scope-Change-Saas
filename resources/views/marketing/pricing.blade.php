@extends('layouts.marketing', ['title' => 'Pricing', 'description' => 'Free to start. Paid plans add unlimited change requests, reminders, Proof Packs, team seats and white-label approval pages.'])

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'Pricing',
    'heading' => 'One approved change usually <span class="marker">pays for a year</span>.',
    'intro' => 'Every paid plan starts with a 14-day trial of Pro. No card needed to start.',
    'center' => true,
])

<section class="mx-auto mt-14 max-w-6xl px-4">
    @include('marketing.partials.plans')
</section>

<section class="mx-auto mt-24 max-w-6xl px-4">
    <h2 class="text-center text-3xl font-semibold tracking-tight">Billing details</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        <div class="group hover-lift rounded-3xl border border-ink/10 bg-white p-7">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-110" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v12H4zM4 11h16M8 15h3"/></svg>
            </span>
            <h3 class="mt-5 text-lg font-semibold">Taxes handled for you</h3>
            <p class="mt-2 text-sm leading-relaxed text-ink/70">Subscriptions are sold by our payment provider, Paddle, acting as merchant of record. Paddle calculates and collects VAT, GST and sales tax for your country and issues compliant invoices.</p>
        </div>
        <div class="group hover-lift rounded-3xl border border-ink/10 bg-white p-7">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-signal-soft text-lime-800 transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-110" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10M9 7h8v8"/></svg>
            </span>
            <h3 class="mt-5 text-lg font-semibold">Change plans anytime</h3>
            <p class="mt-2 text-sm leading-relaxed text-ink/70">Upgrades take effect immediately and are prorated. Downgrades and cancellations take effect at the end of the current billing period. If you go over a lower plan's limits after downgrading, existing data stays readable and you can still export it.</p>
        </div>
        <div class="group hover-lift rounded-3xl bg-ink p-7 text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-signal transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-110" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4v5c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V7z"/></svg>
            </span>
            <h3 class="mt-5 text-lg font-semibold">Fair refunds</h3>
            <p class="mt-2 text-sm leading-relaxed text-white/70">See our <a class="font-semibold text-signal underline decoration-signal/40 underline-offset-2 transition hover:decoration-signal" href="{{ route('legal', 'refund') }}">refund policy</a> for details.</p>
        </div>
    </div>
</section>
@endsection
