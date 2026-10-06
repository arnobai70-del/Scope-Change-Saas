@extends('layouts.public')

@section('body')
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>
<header class="border-b border-neutral-200 bg-white/90 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-600 text-sm text-white">SC</span>
            {{ config('app.name') }}
        </a>
        <div class="hidden items-center gap-6 text-sm text-neutral-600 md:flex">
            <a href="{{ route('marketing.features') }}" class="hover:text-neutral-900">Features</a>
            <a href="{{ route('marketing.how-it-works') }}" class="hover:text-neutral-900">How it works</a>
            <a href="{{ route('pricing') }}" class="hover:text-neutral-900">Pricing</a>
            <a href="{{ route('tools.generator') }}" class="hover:text-neutral-900">Free generator</a>
            <a href="{{ route('blog') }}" class="hover:text-neutral-900">Guides</a>
        </div>
        <div class="flex items-center gap-2 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md bg-neutral-900 px-3 py-2 font-medium text-white hover:bg-neutral-700">Open app</a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-neutral-700 hover:text-neutral-900">Log in</a>
                <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-3 py-2 font-medium text-white hover:bg-indigo-500">Start free</a>
            @endauth
        </div>
    </nav>
</header>

<main id="main">
    @if(session('status'))
        <div class="mx-auto mt-6 max-w-3xl rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>

<footer class="mt-24 border-t border-neutral-200 bg-neutral-50">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 text-sm sm:grid-cols-2 md:grid-cols-4">
        <div>
            <p class="font-semibold">{{ config('app.name') }}</p>
            <p class="mt-2 text-neutral-600">Turn scope creep into approved change revenue before work starts.</p>
        </div>
        <div>
            <p class="font-medium">Product</p>
            <ul class="mt-2 space-y-1 text-neutral-600">
                <li><a href="{{ route('marketing.features') }}" class="hover:underline">Features</a></li>
                <li><a href="{{ route('pricing') }}" class="hover:underline">Pricing</a></li>
                <li><a href="{{ route('marketing.examples.client-approval') }}" class="hover:underline">Client approval example</a></li>
                <li><a href="{{ route('marketing.security') }}" class="hover:underline">Security</a></li>
                <li><a href="{{ route('marketing.status') }}" class="hover:underline">Status</a></li>
            </ul>
        </div>
        <div>
            <p class="font-medium">Resources</p>
            <ul class="mt-2 space-y-1 text-neutral-600">
                <li><a href="{{ route('tools.generator') }}" class="hover:underline">Change request generator</a></li>
                <li><a href="{{ route('tools.calculator') }}" class="hover:underline">Scope creep calculator</a></li>
                <li><a href="{{ route('marketing.templates.change-request') }}" class="hover:underline">Change request template</a></li>
                <li><a href="{{ route('blog') }}" class="hover:underline">Guides</a></li>
                <li><a href="{{ route('marketing.docs') }}" class="hover:underline">Help docs</a></li>
            </ul>
        </div>
        <div>
            <p class="font-medium">Company</p>
            <ul class="mt-2 space-y-1 text-neutral-600">
                <li><a href="{{ route('contact') }}" class="hover:underline">Contact</a></li>
                <li><a href="{{ route('legal', 'terms') }}" class="hover:underline">Terms</a></li>
                <li><a href="{{ route('legal', 'privacy') }}" class="hover:underline">Privacy</a></li>
                <li><a href="{{ route('legal', 'dpa') }}" class="hover:underline">DPA</a></li>
                <li><a href="{{ route('legal', 'refund') }}" class="hover:underline">Refunds</a></li>
                <li><a href="{{ route('marketing.subprocessors') }}" class="hover:underline">Subprocessors</a></li>
            </ul>
        </div>
    </div>
    <p class="pb-8 text-center text-xs text-neutral-500">&copy; {{ date('Y') }} {{ config('app.name') }}. Not legal advice.</p>
</footer>
@endsection
