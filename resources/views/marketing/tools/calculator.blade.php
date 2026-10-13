@extends('layouts.marketing', ['title' => 'Scope creep cost calculator', 'description' => 'Estimate how much unbilled extra work costs you each year.'])

@section('content')
<section class="mx-auto max-w-3xl px-4 pt-14">
    <p class="text-sm font-medium text-brand-600">Free tool</p>
    <h1 class="mt-2 text-3xl font-semibold">How much is scope creep costing you?</h1>
    <form method="GET" action="{{ route('tools.calculator') }}" class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach([
            ['rate', 'Your hourly rate', '0.01'],
            ['hours', 'Hours per unbilled request', '0.5'],
            ['requests', 'Unbilled requests per project', '1'],
            ['projects', 'Projects per year', '1'],
        ] as [$field, $label, $step])
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" step="{{ $step }}" required value="{{ $input[$field] ?? '' }}" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2">
            </div>
        @endforeach
        @if($errors->any())<p class="text-sm text-red-600 sm:col-span-2">{{ $errors->first() }}</p>@endif
        <div class="sm:col-span-2"><button class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg">Calculate</button></div>
    </form>

    @if($result)
        <div class="mt-10 grid gap-4 sm:grid-cols-3" aria-live="polite">
            <div class="rounded-xl border p-5"><p class="text-sm text-ink/55">Per project</p><p class="mt-1 text-2xl font-semibold">{{ number_format($result['per_project'], 2) }}</p></div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-5"><p class="text-sm text-red-700">Per year</p><p class="mt-1 text-2xl font-semibold text-red-800">{{ number_format($result['per_year'], 2) }}</p></div>
            <div class="rounded-xl border p-5"><p class="text-sm text-ink/55">Unpaid hours a year</p><p class="mt-1 text-2xl font-semibold">{{ number_format($result['hours_per_year'], 1) }}</p></div>
        </div>
        <p class="mt-4 text-sm text-ink/65">Amounts are in the same currency as your hourly rate.</p>
        <a href="{{ route('register') }}" class="mt-6 inline-block rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg">Start charging for it, free</a>
    @endif
</section>
@endsection
