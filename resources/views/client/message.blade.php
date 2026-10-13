@extends('layouts.public', ['title' => $title, 'noindex' => true])

@section('body')
<main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">
    <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_70%)]" aria-hidden="true"></div>
    <div class="relative w-full max-w-md rounded-3xl border border-ink/10 bg-white p-8 text-center card-lift">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-paper">@include('partials.logo', ['class' => 'h-7 w-7'])</div>
        <h1 class="mt-5 text-2xl font-semibold">{{ $title }}</h1>
        <p class="mt-3 leading-relaxed text-ink/65">{{ $message }}</p>
    </div>
</main>
@endsection
