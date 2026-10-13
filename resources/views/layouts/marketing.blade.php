@extends('layouts.public')

@php
    $nav = [
        [route('marketing.features'), 'Features'],
        [route('marketing.how-it-works'), 'How it works'],
        [route('pricing'), 'Pricing'],
        [route('tools.generator'), 'Free generator'],
        [route('blog'), 'Guides'],
    ];
@endphp

@section('body')
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>
<header class="sticky top-0 z-40 border-b border-ink/10 bg-paper/85 backdrop-blur-md">
    <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-display text-lg font-semibold tracking-tight">
            @include('partials.logo', ['class' => 'h-8 w-8'])
            {{ config('app.name') }}
        </a>
        <div class="hidden items-center gap-1 text-sm font-medium text-ink/70 md:flex">
            @foreach($nav as [$href, $label])
                <a href="{{ $href }}" @class(['rounded-full px-3 py-1.5 transition hover:bg-ink/5 hover:text-ink', 'bg-ink/5 text-ink' => url()->current() === $href])>{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-2 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-ink">Open app</a>
            @else
                <a href="{{ route('login') }}" class="hidden px-3 py-2 font-medium text-ink/70 hover:text-ink sm:inline">Log in</a>
                <a href="{{ route('register') }}" class="btn-primary">Start free</a>
            @endauth
            <details class="relative md:hidden">
                <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-ink/15 bg-white [&::-webkit-details-marker]:hidden" aria-label="Menu">
                    <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14"/></svg>
                </summary>
                <div class="absolute right-0 mt-2 w-56 rounded-xl border border-ink/10 bg-white p-2 card-lift">
                    @foreach($nav as [$href, $label])
                        <a href="{{ $href }}" class="block rounded-lg px-3 py-2 font-medium hover:bg-paper">{{ $label }}</a>
                    @endforeach
                    @guest
                        <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 font-medium hover:bg-paper">Log in</a>
                    @endguest
                </div>
            </details>
        </div>
    </nav>
</header>

<main id="main">
    @if(session('status'))
        <div class="mx-auto mt-6 max-w-3xl rounded-xl border border-lime-300 bg-signal-soft px-4 py-3 text-sm font-medium text-ink" role="status">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>

<section class="mx-auto mt-28 max-w-6xl px-4">
    <div class="relative overflow-hidden rounded-3xl bg-ink px-6 py-12 text-white sm:px-12 sm:py-16">
        <div class="bg-grid-light absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-600/40 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">The next "quick extra" is <span class="text-signal">billable</span>.</h2>
                <p class="mt-3 text-white/70">Send your first priced change request today. Free plan, no card needed.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn-signal px-5 py-3 text-base">Start free</a>
                <a href="{{ route('marketing.examples.client-approval') }}" class="inline-flex items-center rounded-lg border border-white/20 px-5 py-3 text-base font-semibold text-white hover:bg-white/10">See the client view</a>
            </div>
        </div>
    </div>
</section>

<footer class="mt-16 border-t border-ink/10">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 text-sm sm:grid-cols-2 md:grid-cols-4">
        <div>
            <p class="flex items-center gap-2 font-display text-base font-semibold">@include('partials.logo', ['class' => 'h-7 w-7']) {{ config('app.name') }}</p>
            <p class="mt-3 max-w-xs text-ink/60">Turn scope creep into approved change revenue before work starts.</p>
        </div>
        @foreach([
            'Product' => [
                [route('marketing.features'), 'Features'],
                [route('pricing'), 'Pricing'],
                [route('marketing.examples.client-approval'), 'Client approval example'],
                [route('marketing.security'), 'Security'],
                [route('marketing.status'), 'Status'],
            ],
            'Resources' => [
                [route('tools.generator'), 'Change request generator'],
                [route('tools.calculator'), 'Scope creep calculator'],
                [route('marketing.templates.change-request'), 'Change request template'],
                [route('blog'), 'Guides'],
                [route('marketing.docs'), 'Help docs'],
            ],
            'Company' => [
                [route('contact'), 'Contact'],
                [route('legal', 'terms'), 'Terms'],
                [route('legal', 'privacy'), 'Privacy'],
                [route('legal', 'dpa'), 'DPA'],
                [route('legal', 'refund'), 'Refunds'],
                [route('marketing.subprocessors'), 'Subprocessors'],
            ],
        ] as $heading => $links)
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/45">{{ $heading }}</p>
                <ul class="mt-3 space-y-2 text-ink/70">
                    @foreach($links as [$href, $label])
                        <li><a href="{{ $href }}" class="hover:text-brand-700">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="border-t border-ink/10">
        <p class="mx-auto max-w-6xl px-4 py-6 text-xs text-ink/50">&copy; {{ date('Y') }} {{ config('app.name') }}. Not legal advice.</p>
    </div>
</footer>
@endsection
