@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 pt-14">
    @hasSection('eyebrow')<p class="text-sm font-medium text-indigo-600">@yield('eyebrow')</p>@endif
    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h1>
    @isset($updated)<p class="mt-2 text-sm text-neutral-500">Last updated {{ $updated }}</p>@endisset
    <div class="prose-content mt-8 space-y-4 text-neutral-700 [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-neutral-900 [&_h3]:mt-6 [&_h3]:font-semibold [&_h3]:text-neutral-900 [&_li]:ml-5 [&_li]:list-disc [&_a]:text-indigo-600 [&_a]:underline [&_table]:w-full [&_td]:border-b [&_td]:py-2 [&_td]:pr-4 [&_th]:border-b [&_th]:py-2 [&_th]:pr-4 [&_th]:text-left">
        @yield('prose')
    </div>
</article>
@endsection
