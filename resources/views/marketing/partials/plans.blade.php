<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach($plans as $plan)
        <div class="flex flex-col rounded-xl border {{ $plan['key'] === 'pro' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-neutral-200' }} bg-white p-6">
            <p class="font-semibold">{{ $plan['name'] }}</p>
            <p class="mt-1 text-sm text-neutral-600">{{ $plan['description'] }}</p>
            <p class="mt-4"><span class="text-3xl font-semibold">{{ $plan['monthly'] }}</span><span class="text-sm text-neutral-500"> / month</span></p>
            @if($plan['key'] !== 'free')<p class="text-xs text-neutral-500">or {{ $plan['yearly'] }} billed yearly</p>@endif
            <ul class="mt-4 flex-1 space-y-2 text-sm">
                @foreach($plan['highlights'] as $item)
                    <li class="flex gap-2"><span aria-hidden="true" class="text-indigo-600">✓</span>{{ $item }}</li>
                @endforeach
            </ul>
            <a href="{{ route('register') }}" class="mt-6 rounded-md {{ $plan['key'] === 'pro' ? 'bg-indigo-600 text-white hover:bg-indigo-500' : 'border border-neutral-300 hover:bg-neutral-50' }} px-4 py-2 text-center text-sm font-medium">
                {{ $plan['key'] === 'free' ? 'Start free' : 'Start 14-day trial' }}
            </a>
        </div>
    @endforeach
</div>
<p class="mt-4 text-center text-xs text-neutral-500">Prices in USD. Local taxes are calculated at checkout by our payment provider, who acts as merchant of record.</p>
