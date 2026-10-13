@extends('layouts.marketing', ['title' => 'Pricing', 'description' => 'Free to start. Paid plans add unlimited change requests, reminders, Proof Packs, team seats and white-label approval pages.'])

@section('content')
<section class="mx-auto max-w-6xl px-4 pt-16">
    <h1 class="text-center text-4xl font-semibold">Pricing</h1>
    <p class="mx-auto mt-3 max-w-xl text-center text-ink/65">Every paid plan starts with a 14-day trial of Pro. No card needed to start.</p>
    <div class="mt-10">@include('marketing.partials.plans')</div>

    <div class="mx-auto mt-16 max-w-3xl space-y-4 text-sm text-ink/75">
        <h2 class="text-xl font-semibold text-ink">Billing details</h2>
        <p>Subscriptions are sold by our payment provider, Paddle, acting as merchant of record. Paddle calculates and collects VAT, GST and sales tax for your country and issues compliant invoices.</p>
        <p>Upgrades take effect immediately and are prorated. Downgrades and cancellations take effect at the end of the current billing period. If you go over a lower plan's limits after downgrading, existing data stays readable and you can still export it.</p>
        <p>See our <a class="text-brand-600 underline" href="{{ route('legal', 'refund') }}">refund policy</a> for details.</p>
    </div>
</section>
@endsection
