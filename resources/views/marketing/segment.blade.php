@extends('layouts.marketing', ['title' => $segment['title'], 'description' => $segment['intro']])

@section('content')
<section class="mx-auto max-w-4xl px-4 pt-16 text-center">
    <p class="text-sm font-medium text-indigo-600">{{ $segment['title'] }}</p>
    <h1 class="mt-3 text-4xl font-semibold tracking-tight">{{ $segment['headline'] }}</h1>
    <p class="mx-auto mt-5 max-w-2xl text-lg text-neutral-600">{{ $segment['intro'] }}</p>
    <a href="{{ route('register') }}" class="mt-8 inline-block rounded-md bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-500">Start free</a>
</section>
<section class="mx-auto mt-16 max-w-3xl px-4">
    <h2 class="text-xl font-semibold">Requests that become paid changes</h2>
    <ul class="mt-4 space-y-2">
        @foreach($segment['examples'] as $example)
            <li class="rounded-lg border border-neutral-200 px-4 py-3">{{ $example }}</li>
        @endforeach
    </ul>
    <p class="mt-8 text-neutral-600">Each one becomes a priced change request linked to the original scope, approved by your client on a no-login page, with reminders and a Proof Pack for your records.</p>
</section>
@endsection
