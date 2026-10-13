{{-- Shared hero band for marketing sub-pages. Pass $eyebrow, $heading (HTML-safe slot), $intro and optional $center. --}}
<section class="relative overflow-hidden border-b border-ink/10">
    <div class="bg-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)]" aria-hidden="true"></div>
    <div class="absolute -left-32 -top-20 h-72 w-72 rounded-full bg-brand-300/35 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -right-24 top-10 h-64 w-64 rounded-full bg-signal/35 blur-3xl" aria-hidden="true"></div>
    <div @class(['relative mx-auto max-w-6xl px-4 pb-14 pt-14 sm:pt-20', 'text-center' => $center ?? false])>
        <p class="eyebrow"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>{{ $eyebrow }}</p>
        <h1 @class(['mt-5 max-w-3xl text-4xl font-semibold leading-[1.05] tracking-tight sm:text-6xl', 'mx-auto' => $center ?? false])>{!! $heading !!}</h1>
        @isset($intro)<p @class(['mt-5 max-w-2xl text-lg leading-relaxed text-ink/70', 'mx-auto' => $center ?? false])>{{ $intro }}</p>@endisset
        {{ $slot ?? '' }}
    </div>
</section>
