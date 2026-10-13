@extends('layouts.marketing', ['title' => 'Free change request generator', 'description' => 'Write a clear, professional change request for extra client work in seconds. Free, no sign-up.'])

@section('content')
<section class="mx-auto grid max-w-6xl gap-10 px-4 pt-14 lg:grid-cols-2">
    <div>
        <p class="text-sm font-medium text-brand-600">Free tool</p>
        <h1 class="mt-2 text-3xl font-semibold">Change request generator</h1>
        <p class="mt-2 text-ink/65">Fill in the details and get a calm, professional message you can send to your client.</p>
        <form method="GET" action="{{ route('tools.generator') }}" class="mt-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="client" class="block text-sm font-medium">Client first name</label><input id="client" name="client" maxlength="120" value="{{ $input['client'] ?? '' }}" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2"></div>
                <div><label for="project" class="block text-sm font-medium">Project</label><input id="project" name="project" maxlength="160" value="{{ $input['project'] ?? '' }}" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2"></div>
            </div>
            <div><label for="change" class="block text-sm font-medium">What did the client ask for?</label><textarea id="change" name="change" rows="3" maxlength="2000" required class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2">{{ $input['change'] ?? '' }}</textarea></div>
            <div><label for="original" class="block text-sm font-medium">What did the original scope include? <span class="text-ink/55">(optional)</span></label><textarea id="original" name="original" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2">{{ $input['original'] ?? '' }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="price" class="block text-sm font-medium">Price</label><input id="price" name="price" inputmode="decimal" maxlength="20" value="{{ $input['price'] ?? '' }}" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2"></div>
                <div><label for="currency" class="block text-sm font-medium">Currency</label>
                    <select id="currency" name="currency" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2">
                        @foreach($currencies as $c)<option value="{{ $c }}" @selected(($input['currency'] ?? 'USD') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div><label for="days" class="block text-sm font-medium">Extra days</label><input id="days" name="days" type="number" min="0" max="365" value="{{ $input['days'] ?? '' }}" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2"></div>
            </div>
            <div><label for="payment" class="block text-sm font-medium">Payment</label>
                <select id="payment" name="payment" class="mt-1 w-full rounded-md border border-ink/20 px-3 py-2">
                    @foreach(['none' => 'No special condition', 'before_start' => 'Before work starts', 'before_handoff' => 'Before handoff'] as $value => $label)<option value="{{ $value }}" @selected(($input['payment'] ?? 'none') === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
            <button class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg">Generate</button>
        </form>
    </div>
    <div>
        <div class="rounded-xl border border-ink/10 bg-paper p-6">
            @if($output)
                <p class="mb-3 text-sm font-medium text-ink/55">Your change request</p>
                {!! $output !!}
            @else
                <p class="text-ink/55">Your generated message will appear here.</p>
            @endif
        </div>
        <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 p-6">
            <p class="font-semibold">Want a one-click approval instead of "reply Approved"?</p>
            <p class="mt-1 text-sm text-ink/75">Send this as a secure approval link with reminders and a timestamped record.</p>
            <a href="{{ route('register') }}" class="mt-4 inline-block rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg">Start free</a>
        </div>
    </div>
</section>
@endsection
