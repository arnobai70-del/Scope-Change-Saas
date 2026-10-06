@extends('layouts.public', ['title' => $revision->title.' · '.$changeRequest->reference, 'noindex' => true])

@php
    /** @var \App\Models\ChangeRequest $changeRequest */
    /** @var \App\Models\ChangeRequestRevision $revision */
    $brand = $workspace->brand_color ?: '#4f46e5';
    $tz = $workspace->timezone;
    $route = fn (string $name) => $token ? route($name, [$changeRequest->public_id, $token]) : '#';
@endphp

@section('body')
<div class="min-h-screen bg-neutral-50">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-2xl items-center gap-3 px-4 py-4">
            @if($workspace->logoUrl())
                <img src="{{ $workspace->logoUrl() }}" alt="{{ $workspace->name }} logo" class="h-9 w-9 rounded object-contain">
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded text-sm font-semibold text-white" style="background: {{ $brand }}">{{ mb_substr($workspace->name, 0, 1) }}</div>
            @endif
            <div class="min-w-0">
                <p class="truncate font-semibold">{{ $workspace->name }}</p>
                <p class="text-xs text-neutral-500">Change request {{ $changeRequest->reference }} · Revision {{ $revision->revision_no }}</p>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-2xl space-y-5 px-4 py-6">
        @if($preview ?? false)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Preview: this is what your client will see. Buttons are disabled.</div>
        @endif

        @if(session('status'))
            <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @if($decision)
            <section class="rounded-xl border bg-white p-5" aria-labelledby="decision-heading">
                <h1 id="decision-heading" class="text-xl font-semibold">
                    {{ $decision->decision->value === 'approved' ? 'Change approved' : 'Change declined' }}
                </h1>
                <p class="mt-1 text-sm text-neutral-600">
                    {{ ucfirst($decision->decision->value) }} by {{ $decision->client_name }} ({{ $decision->client_email }})
                    on {{ $decision->decided_at->copy()->setTimezone($tz)->format('j M Y, H:i T') }}
                    <span class="text-neutral-400">({{ $decision->decided_at->copy()->utc()->format('Y-m-d H:i:s') }} UTC)</span>.
                </p>
                <p class="mt-1 text-sm text-neutral-600">Reference {{ $changeRequest->reference }} · Revision {{ $revision->revision_no }}</p>
                <button type="button" onclick="window.print()" class="mt-3 text-sm font-medium underline print:hidden">Print or save a copy</button>
            </section>
        @endif

        <section class="rounded-xl border bg-white p-5">
            <p class="text-sm text-neutral-600">{{ $workspace->name }} is asking for your approval on a change to <strong>{{ $project->title }}</strong>.</p>
            @unless($decision)
                <h1 class="mt-3 text-2xl font-semibold leading-tight">{{ $revision->title }}</h1>
            @else
                <h2 class="mt-3 text-xl font-semibold leading-tight">{{ $revision->title }}</h2>
            @endunless
            <div class="mt-3 whitespace-pre-line text-neutral-800">{{ $revision->description }}</div>
        </section>

        @if($revision->scope_reason || $revision->scope_excerpt || $revision->scopeItems->isNotEmpty())
            <section class="rounded-xl border bg-white p-5">
                <h2 class="font-semibold">Why this is outside the agreed scope</h2>
                @if($revision->scope_reason)
                    <p class="mt-2 whitespace-pre-line text-neutral-800">{{ $revision->scope_reason }}</p>
                @endif
                @if($revision->scopeItems->isNotEmpty() || $revision->scope_excerpt)
                    <div class="mt-3 rounded-lg bg-neutral-50 p-3 text-sm">
                        <p class="font-medium text-neutral-700">Original agreed scope</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-neutral-700">
                            @foreach($revision->scopeItems as $item)
                                <li><span class="text-neutral-500">{{ $item->type->label() }}:</span> {{ $item->title }}</li>
                            @endforeach
                        </ul>
                        @if($revision->scope_excerpt)
                            <p class="mt-2 whitespace-pre-line text-neutral-700">{{ $revision->scope_excerpt }}</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-xl border bg-white p-5" aria-label="Impact">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">Price</dt>
                    <dd class="mt-1 text-xl font-semibold">{{ $revision->price()->format() }}</dd>
                    <dd class="text-xs text-neutral-500">{{ $revision->currency }}{{ $revision->price_minor === 0 ? ' · no charge' : '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">Timeline</dt>
                    <dd class="mt-1 font-medium">{{ $revision->timelineLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">Payment</dt>
                    <dd class="mt-1 font-medium">{{ $revision->payment_rule->label() }}</dd>
                </div>
            </dl>
            @if($revision->terms_note)
                <p class="mt-4 whitespace-pre-line border-t pt-3 text-sm text-neutral-700">{{ $revision->terms_note }}</p>
            @endif
        </section>

        @if($payment && $payment->status->value !== 'not_required' && $decision && $decision->decision->value === 'approved')
            <section class="rounded-xl border bg-white p-5" aria-labelledby="payment-heading">
                <h2 id="payment-heading" class="font-semibold">Payment</h2>
                <p class="mt-1 text-sm text-neutral-600">Status: {{ $payment->status->label() }} · {{ $payment->amount()->format() }}</p>
                @if($revision->payment_instructions)
                    <p class="mt-2 whitespace-pre-line text-sm text-neutral-800">{{ $revision->payment_instructions }}</p>
                @endif
                <p class="mt-2 text-xs text-neutral-500">Payment goes directly to {{ $workspace->name }}. This page never handles your money.</p>
                @if($token && $payment->status->value === 'pending')
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        @if($payment->external_url)
                            <a href="{{ $route('client.pay') }}" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 font-medium text-white" style="background: {{ $brand }}">Pay {{ $payment->amount()->format() }}</a>
                        @endif
                        <form method="POST" action="{{ $route('client.payment-sent') }}" class="flex flex-1 flex-col gap-2 sm:flex-row">
                            @csrf
                            <label for="reference" class="sr-only">Payment reference (optional)</label>
                            <input id="reference" name="reference" maxlength="120" placeholder="Payment reference (optional)" class="flex-1 rounded-lg border px-3 py-2 text-sm">
                            <button class="rounded-lg border px-4 py-2 text-sm font-medium">I've sent the payment</button>
                        </form>
                    </div>
                @endif
            </section>
        @endif

        @if($canDecide)
            <section class="rounded-xl border-2 bg-white p-5" style="border-color: {{ $brand }}" aria-labelledby="approve-heading">
                <h2 id="approve-heading" class="font-semibold">Your decision</h2>
                <form method="POST" action="{{ $route('client.approve') }}" class="mt-3 space-y-3" id="decision-form">
                    @csrf
                    <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                    <div class="hidden" aria-hidden="true"><label>Leave empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="client_name" class="text-sm font-medium">Your name</label>
                            <input id="client_name" name="client_name" required maxlength="120" value="{{ old('client_name', $changeRequest->recipient_name) }}" autocomplete="name" class="mt-1 w-full rounded-lg border px-3 py-2">
                        </div>
                        <div>
                            <label for="client_email" class="text-sm font-medium">Your email</label>
                            <input id="client_email" name="client_email" type="email" required maxlength="255" value="{{ old('client_email', $changeRequest->recipient_email) }}" autocomplete="email" class="mt-1 w-full rounded-lg border px-3 py-2">
                        </div>
                    </div>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="accept_terms" value="1" class="mt-1" required>
                        <span>I approve this change, including the price of {{ $revision->price()->format() }}, the timeline impact and the payment condition above.</span>
                    </label>
                    <button class="w-full rounded-lg px-4 py-3 text-base font-semibold text-white" style="background: {{ $brand }}">Approve change</button>
                </form>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-neutral-700">Decline this change</summary>
                    <form method="POST" action="{{ $route('client.decline') }}" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                        <input type="hidden" name="client_name" value="{{ old('client_name', $changeRequest->recipient_name) }}" data-mirror="client_name">
                        <input type="hidden" name="client_email" value="{{ old('client_email', $changeRequest->recipient_email) }}" data-mirror="client_email">
                        <label for="reason" class="text-sm font-medium">Reason (optional)</label>
                        <textarea id="reason" name="reason" maxlength="2000" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                        <button class="w-full rounded-lg border px-4 py-2.5 font-medium">Decline change</button>
                    </form>
                </details>
            </section>

            <section class="rounded-xl border bg-white p-5" aria-labelledby="question-heading">
                <h2 id="question-heading" class="font-semibold">Ask a question</h2>
                <form method="POST" action="{{ $route('client.comment') }}" class="mt-3 space-y-3">
                    @csrf
                    <div class="hidden" aria-hidden="true"><label>Leave empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <input type="hidden" name="client_name" value="{{ old('client_name', $changeRequest->recipient_name) }}" data-mirror="client_name">
                    <input type="hidden" name="client_email" value="{{ old('client_email', $changeRequest->recipient_email) }}" data-mirror="client_email">
                    <label for="body" class="sr-only">Your question</label>
                    <textarea id="body" name="body" required maxlength="5000" rows="3" placeholder="What would you like to know?" class="w-full rounded-lg border px-3 py-2"></textarea>
                    <button class="rounded-lg border px-4 py-2 text-sm font-medium">Send question</button>
                </form>
            </section>
        @endif

        @if($comments->isNotEmpty())
            <section class="rounded-xl border bg-white p-5" aria-labelledby="conversation-heading">
                <h2 id="conversation-heading" class="font-semibold">Conversation</h2>
                <ol class="mt-3 space-y-3">
                    @foreach($comments as $comment)
                        <li class="rounded-lg {{ $comment->actor_type->value === 'client' ? 'bg-neutral-50' : 'bg-indigo-50' }} p-3 text-sm">
                            <p class="font-medium">{{ $comment->actor_name ?? ucfirst($comment->actor_type->value) }} <span class="font-normal text-neutral-500">· {{ $comment->created_at?->copy()->setTimezone($tz)->format('j M Y, H:i') }}</span></p>
                            <p class="mt-1 whitespace-pre-line">{{ $comment->body }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <footer class="space-y-1 pb-8 text-center text-xs text-neutral-500">
            <p>Reference {{ $changeRequest->reference }} · Revision {{ $revision->revision_no }}@if($changeRequest->expires_at && $canDecide) · Link expires {{ $changeRequest->expires_at->copy()->setTimezone($tz)->format('j M Y') }}@endif</p>
            <p>This page records your decision with a timestamp so both sides have the same record. <a class="underline" href="{{ route('legal', 'privacy') }}">Privacy</a> · <a class="underline" href="{{ route('legal', 'terms') }}">Terms</a></p>
            @if($showBranding)
                <p><a href="{{ route('home') }}" class="underline">Powered by {{ config('app.name') }}</a></p>
            @endif
        </footer>
    </main>
</div>
<script>
    // Keep the name/email entered in the approval form in sync with the decline and question forms.
    document.querySelectorAll('#client_name, #client_email').forEach(function (input) {
        input.addEventListener('input', function () {
            document.querySelectorAll('[data-mirror="' + input.id + '"]').forEach(function (hidden) { hidden.value = input.value; });
        });
    });
    // Prevent accidental double submission.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
        });
    });
</script>
@endsection
