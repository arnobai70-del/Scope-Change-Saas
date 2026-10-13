@extends('layouts.marketing', ['title' => 'Free change request generator', 'description' => 'Write a clear, professional change request for extra client work in seconds. Free, no sign-up.'])

@php($field = 'mt-1.5 w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 transition hover:border-ink/30 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15')

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'Free tool',
    'heading' => 'Change request <span class="marker">generator</span>',
    'intro' => 'Fill in the details and get a calm, professional message you can send to your client.',
])

<section class="mx-auto mt-12 grid max-w-6xl items-start gap-8 px-4 lg:grid-cols-[1.05fr_0.95fr]">
    <form method="GET" action="{{ route('tools.generator') }}" class="space-y-6 rounded-3xl border border-ink/10 bg-white p-6 card-lift sm:p-8">
        <fieldset class="space-y-4">
            <legend class="flex items-center gap-2 font-display text-lg font-semibold"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-sm text-white">1</span>Who and what</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="client" class="block text-sm font-medium">Client first name</label><input id="client" name="client" maxlength="120" value="{{ $input['client'] ?? '' }}" class="{{ $field }}"></div>
                <div><label for="project" class="block text-sm font-medium">Project</label><input id="project" name="project" maxlength="160" value="{{ $input['project'] ?? '' }}" class="{{ $field }}"></div>
            </div>
            <div><label for="change" class="block text-sm font-medium">What did the client ask for?</label><textarea id="change" name="change" rows="3" maxlength="2000" required class="{{ $field }}">{{ $input['change'] ?? '' }}</textarea></div>
            <div><label for="original" class="block text-sm font-medium">What did the original scope include? <span class="text-ink/55">(optional)</span></label><textarea id="original" name="original" rows="2" maxlength="1000" class="{{ $field }}">{{ $input['original'] ?? '' }}</textarea></div>
        </fieldset>
        <hr class="border-dashed border-ink/15">
        <fieldset class="space-y-4">
            <legend class="flex items-center gap-2 font-display text-lg font-semibold"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-signal text-sm text-ink">2</span>Price and timing</legend>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="price" class="block text-sm font-medium">Price</label><input id="price" name="price" inputmode="decimal" maxlength="20" value="{{ $input['price'] ?? '' }}" class="{{ $field }}"></div>
                <div><label for="currency" class="block text-sm font-medium">Currency</label>
                    <select id="currency" name="currency" class="{{ $field }}">
                        @foreach($currencies as $c)<option value="{{ $c }}" @selected(($input['currency'] ?? 'USD') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div><label for="days" class="block text-sm font-medium">Extra days</label><input id="days" name="days" type="number" min="0" max="365" value="{{ $input['days'] ?? '' }}" class="{{ $field }}"></div>
            </div>
            <div><label for="payment" class="block text-sm font-medium">Payment</label>
                <select id="payment" name="payment" class="{{ $field }}">
                    @foreach(['none' => 'No special condition', 'before_start' => 'Before work starts', 'before_handoff' => 'Before handoff'] as $value => $label)<option value="{{ $value }}" @selected(($input['payment'] ?? 'none') === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
        </fieldset>
        @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="group btn-primary w-full py-3 text-base sm:w-auto sm:px-6">Generate <span class="nudge" aria-hidden="true">→</span></button>
    </form>

    <div class="space-y-6 lg:sticky lg:top-24">
        <div class="overflow-hidden rounded-3xl border border-ink/10 bg-white card-lift">
            <div class="flex items-center gap-2 border-b border-ink/10 bg-paper px-5 py-3" aria-hidden="true">
                <span class="h-3 w-3 rounded-full bg-red-400"></span><span class="h-3 w-3 rounded-full bg-amber-400"></span><span class="h-3 w-3 rounded-full bg-lime-500"></span>
                <span class="ml-3 text-xs font-semibold text-ink/50">New message</span>
            </div>
            <div class="p-6">
                @if($output)
                    <p class="mb-3 text-sm font-medium text-ink/55">Your change request</p>
                    {!! $output !!}
                @else
                    <div class="flex flex-col items-center py-10 text-center">
                        <span class="flex h-12 w-12 rotate-[-6deg] items-center justify-center rounded-2xl bg-signal text-xl" aria-hidden="true">✍️</span>
                        <p class="mt-4 text-ink/55">Your generated message will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="relative overflow-hidden rounded-3xl bg-ink p-6 text-white">
            <div class="bg-grid-light absolute inset-0" aria-hidden="true"></div>
            <div class="relative">
                <p class="font-display text-lg font-semibold">Want a one-click approval instead of "reply Approved"?</p>
                <p class="mt-1 text-sm text-white/70">Send this as a secure approval link with reminders and a timestamped record.</p>
                <a href="{{ route('register') }}" class="btn-signal mt-5">Start free</a>
            </div>
        </div>
    </div>
</section>
@endsection
