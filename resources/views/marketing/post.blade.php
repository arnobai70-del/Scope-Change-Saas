@extends('layouts.marketing', ['title' => $post['title'], 'description' => $post['description']])

@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post['title'], 'description' => $post['description']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 pt-16">
    <a href="{{ route('blog') }}" class="text-sm text-indigo-600 hover:underline">← All guides</a>
    <h1 class="mt-4 text-4xl font-semibold tracking-tight">{{ $post['title'] }}</h1>
    <div class="mt-8 space-y-5 text-lg leading-relaxed text-neutral-700">
        @foreach($post['body'] as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
    <div class="mt-12 rounded-xl border border-indigo-200 bg-indigo-50 p-6">
        <p class="font-semibold">Send your next change request in two minutes</p>
        <p class="mt-1 text-sm text-neutral-700">Free plan, no card needed.</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Start free</a>
    </div>
</article>
@endsection
