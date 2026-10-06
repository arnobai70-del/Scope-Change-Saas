@extends('layouts.public', ['title' => $title, 'noindex' => true])

@section('body')
<main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12 text-center">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>
    <p class="mt-3 text-neutral-600">{{ $message }}</p>
</main>
@endsection
