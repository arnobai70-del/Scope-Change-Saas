<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach($plans as $plan)
        @php($featured = $plan['key'] === 'pro')
        <div @class([
            'hover-lift relative flex flex-col rounded-3xl p-6',
            'bg-ink text-white card-lift lg:-my-3 lg:py-9' => $featured,
            'border border-ink/10 bg-white hover:border-brand-300' => ! $featured,
        ])>
            @if($featured)
                <span class="absolute -top-3 left-6 rounded-full bg-signal px-3 py-1 text-xs font-bold text-ink">Most popular</span>
            @endif
            <p class="font-display text-lg font-semibold">{{ $plan['name'] }}</p>
            <p @class(['mt-1 text-sm', 'text-white/65' => $featured, 'text-ink/60' => ! $featured])>{{ $plan['description'] }}</p>
            <p class="mt-5"><span class="font-display text-4xl font-semibold tracking-tight">{{ $plan['monthly'] }}</span><span @class(['text-sm', 'text-white/60' => $featured, 'text-ink/50' => ! $featured])> / month</span></p>
            @if($plan['key'] !== 'free')<p @class(['text-xs', 'text-white/55' => $featured, 'text-ink/50' => ! $featured])>or {{ $plan['yearly'] }} billed yearly</p>@endif
            <ul class="mt-5 flex-1 space-y-2.5 text-sm">
                @foreach($plan['highlights'] as $item)
                    <li class="flex gap-2.5">
                        <span aria-hidden="true" @class(['mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold', 'bg-signal text-ink' => $featured, 'bg-brand-100 text-brand-700' => ! $featured])>✓</span>
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('register') }}" @class(['mt-7', 'btn-signal' => $featured, 'btn-outline' => ! $featured])>
                {{ $plan['key'] === 'free' ? 'Start free' : 'Start 14-day trial' }}
            </a>
        </div>
    @endforeach
</div>
<p class="mt-6 text-center text-xs text-ink/50">Prices in USD. Local taxes are calculated at checkout by our payment provider, who acts as merchant of record.</p>
