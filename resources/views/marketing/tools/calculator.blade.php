@extends('layouts.marketing', ['title' => 'Scope creep cost calculator', 'description' => 'Estimate how much unbilled extra work costs you each year.'])

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'Free tool',
    'heading' => 'How much is scope creep <span class="marker">costing you?</span>',
    'intro' => 'Estimate how much unbilled extra work costs you each year.',
])

<section class="mx-auto mt-12 max-w-4xl px-4">
    <form method="GET" action="{{ route('tools.calculator') }}" class="grid gap-5 rounded-3xl border border-ink/10 bg-white p-6 card-lift sm:grid-cols-2 sm:p-8">
        @foreach([
            ['rate', 'Your hourly rate', '0.01', '💵'],
            ['hours', 'Hours per unbilled request', '0.5', '⏱️'],
            ['requests', 'Unbilled requests per project', '1', '📨'],
            ['projects', 'Projects per year', '1', '📁'],
        ] as [$field, $label, $step, $icon])
            <div class="group rounded-2xl border border-ink/10 bg-paper p-4 transition hover:border-brand-300 hover:bg-white">
                <label for="{{ $field }}" class="flex items-center gap-2 text-sm font-medium"><span aria-hidden="true">{{ $icon }}</span>{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" step="{{ $step }}" required value="{{ $input[$field] ?? '' }}" class="mt-2 w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 font-display text-lg font-semibold transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15">
            </div>
        @endforeach
        @if($errors->any())<p class="text-sm text-red-600 sm:col-span-2">{{ $errors->first() }}</p>@endif
        <div class="sm:col-span-2"><button class="group btn-primary px-6 py-3 text-base">Calculate <span class="nudge" aria-hidden="true">→</span></button></div>
    </form>

    @if($result)
        <div class="mt-8 grid gap-4 sm:grid-cols-3" aria-live="polite">
            <div class="hover-lift rounded-3xl border border-ink/10 bg-white p-6"><p class="text-sm text-ink/55">Per project</p><p class="mt-2 font-display text-3xl font-semibold">{{ number_format($result['per_project'], 2) }}</p></div>
            <div class="hover-lift relative overflow-hidden rounded-3xl bg-ink p-6 text-white"><p class="text-sm text-white/60">Per year</p><p class="mt-2 font-display text-4xl font-semibold text-signal">{{ number_format($result['per_year'], 2) }}</p><span class="stamp absolute right-4 top-4 border-red-400 bg-red-500/10 text-red-300">Unbilled</span></div>
            <div class="hover-lift rounded-3xl border border-ink/10 bg-white p-6"><p class="text-sm text-ink/55">Unpaid hours a year</p><p class="mt-2 font-display text-3xl font-semibold">{{ number_format($result['hours_per_year'], 1) }}</p></div>
        </div>
        <p class="mt-4 text-sm text-ink/65">Amounts are in the same currency as your hourly rate.</p>
        <a href="{{ route('register') }}" class="group btn-primary mt-6">Start charging for it, free <span class="nudge" aria-hidden="true">→</span></a>
    @endif
</section>
@endsection
