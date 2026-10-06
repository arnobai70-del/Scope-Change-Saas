<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @isset($description)<meta name="description" content="{{ $description }}">@endisset
    @if($noindex ?? false)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        @isset($description)<meta property="og:description" content="{{ $description }}">@endisset
        <meta property="og:type" content="website">
    @endif
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="min-h-screen bg-white font-sans text-neutral-900 antialiased">
    @yield('body')
</body>
</html>
