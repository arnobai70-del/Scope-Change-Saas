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
        <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
        @if(filled($seo['og_image'] ?? null))
            <meta property="og:image" content="{{ $seo['og_image'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="{{ $seo['og_image'] }}">
        @endif
    @endif
    @if(filled($seo['google_verification'] ?? null))
        <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
    @endif
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#14112b">
    @vite(['resources/css/app.css'])
    @stack('head')
    @if(filled($seo['ga_id'] ?? null))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo['ga_id'] }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($seo['ga_id']));</script>
    @endif
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    @yield('body')
</body>
</html>
