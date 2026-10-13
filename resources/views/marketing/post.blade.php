@extends('layouts.marketing', ['title' => $post['title'], 'description' => $post['description']])

@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post['title'], 'description' => $post['description']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="relative overflow-hidden border-b border-ink/10">
    <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)]" aria-hidden="true"></div>
    <div class="absolute -right-24 top-0 h-64 w-64 rounded-full bg-signal/35 blur-3xl" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-3xl px-4 pb-12 pt-14">
        <a href="{{ route('blog') }}" class="group inline-flex items-center gap-1.5 rounded-full border border-ink/10 bg-white px-3 py-1 text-sm font-medium text-brand-700 transition hover:border-brand-300"><span class="inline-block transition-transform group-hover:-translate-x-0.5" aria-hidden="true">←</span> All guides</a>
        <h1 class="mt-6 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ $post['title'] }}</h1>
        <p class="mt-4 text-lg leading-relaxed text-ink/65">{{ $post['description'] }}</p>
    </div>
</div>
<article class="mx-auto max-w-3xl px-4 pt-12">
    <div class="space-y-6 text-lg leading-relaxed text-ink/75 [&>p:first-child]:first-letter:float-left [&>p:first-child]:first-letter:mr-3 [&>p:first-child]:first-letter:font-display [&>p:first-child]:first-letter:text-6xl [&>p:first-child]:first-letter:font-semibold [&>p:first-child]:first-letter:leading-none [&>p:first-child]:first-letter:text-brand-600">
        @foreach($post['body'] as $paragraph)
            @if(str_starts_with($paragraph, 'Template:'))
                <blockquote class="rounded-2xl border-l-4 border-signal bg-white p-6 text-base italic text-ink card-lift">{{ $paragraph }}</blockquote>
            @else
                <p>{{ $paragraph }}</p>
            @endif
        @endforeach
    </div>
    <div class="relative mt-14 overflow-hidden rounded-3xl bg-ink p-8 text-white">
        <div class="bg-grid-light absolute inset-0" aria-hidden="true"></div>
        <div class="relative">
            <p class="font-display text-2xl font-semibold">Send your next change request in two minutes</p>
            <p class="mt-1 text-sm text-white/70">Free plan, no card needed.</p>
            <a href="{{ route('register') }}" class="btn-signal mt-5">Start free</a>
        </div>
    </div>
</article>
@endsection
