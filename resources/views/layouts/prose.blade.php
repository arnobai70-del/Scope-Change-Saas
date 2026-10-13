@extends('layouts.marketing')

@section('content')
<div class="relative overflow-hidden border-b border-ink/10">
    <div class="bg-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-3xl px-4 pb-10 pt-16">
        @hasSection('eyebrow')<p class="eyebrow"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>@yield('eyebrow')</p>@endif
        <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ $title }}</h1>
        @isset($updated)<p class="mt-3 text-sm text-ink/55">Last updated {{ $updated }}</p>@endisset
    </div>
</div>
<article class="mx-auto max-w-3xl px-4 pt-10">
    <div class="prose-content space-y-4 leading-relaxed text-ink/75 [&_h2]:mt-12 [&_h2]:font-display [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-ink [&_h3]:mt-6 [&_h3]:font-semibold [&_h3]:text-ink [&_li]:ml-5 [&_li]:list-disc [&_li]:marker:text-brand-600 [&_a]:font-medium [&_a]:text-brand-700 [&_a]:underline [&_a]:decoration-brand-300 [&_a]:underline-offset-2 [&_table]:w-full [&_td]:border-b [&_td]:border-ink/10 [&_td]:py-2 [&_td]:pr-4 [&_th]:border-b [&_th]:border-ink/20 [&_th]:py-2 [&_th]:pr-4 [&_th]:text-left [&_th]:text-ink">
        @yield('prose')
    </div>
</article>
@endsection
