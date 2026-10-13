@extends('layouts.public', ['title' => $revision->title.' · '.$changeRequest->reference, 'noindex' => true])

@php
    /** @var \App\Models\ChangeRequest $changeRequest */
    /** @var \App\Models\ChangeRequestRevision $revision */
    $brand = $workspace->brand_color ?: '#5b3df5';
    $tz = $workspace->timezone;
    $route = fn (string $name) => $token ? route($name, [$changeRequest->public_id, $token]) : '#';
@endphp

@section('body')
<div class="min-h-screen bg-paper">
    <header class="border-b border-ink/10 bg-white">
        <div class="h-1.5" style="background: {{ $brand }}"></div>
        <div class="mx-auto flex max-w-2xl items-center gap-3 px-4 py-4">
            @if($workspace->logoUrl())
                <img src="{{ $workspace->logoUrl() }}" alt="{{ $workspace->name }} logo" class="h-10 w-10 rounded-xl object-contain">
            @else
                <div class="flex h-10 w-10 items-center justify-center rounded-xl font-display text-base font-semibold text-white" style="background: {{ $brand }}">{{ mb_substr($workspace->name, 0, 1) }}</div>
            @endif
            <div class="min-w-0">
                <p class="truncate font-display text-lg font-semibold leading-tight">{{ $workspace->name }}</p>
                <p class="text-xs text-ink/55">Change request {{ $changeRequest->reference }} · Revision {{ $revision->revision_no }}</p>
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
            <section class="relative overflow-hidden rounded-2xl border border-ink/10 bg-white p-6 card-lift" aria-labelledby="decision-heading">
                <span class="stamp absolute right-5 top-5 {{ $decision->decision->value === 'approved' ? 'border-lime-600 bg-signal-soft text-lime-800' : 'border-red-500 bg-red-50 text-red-700' }}" aria-hidden="true">{{ $decision->decision->value === 'approved' ? 'Approved' : 'Declined' }}</span>
                <h1 id="decision-heading" class="pr-28 text-2xl font-semibold">
                    {{ $decision->decision->value === 'approved' ? 'Change approved' : 'Change declined' }}
                </h1>
                <p class="mt-1 text-sm text-ink/65">
                    {{ ucfirst($decision->decision->value) }} by {{ $decision->client_name }} ({{ $decision->client_email }})
                    on {{ $decision->decided_at->copy()->setTimezone($tz)->format('j M Y, H:i T') }}
                    <span class="text-ink/40">({{ $decision->decided_at->copy()->utc()->format('Y-m-d H:i:s') }} UTC)</span>.
                </p>
                <p class="mt-1 text-sm text-ink/65">Reference {{ $changeRequest->reference }} · Revision {{ $revision->revision_no }}</p>
                <button type="button" onclick="window.print()" class="mt-3 text-sm font-medium underline print:hidden">Print or save a copy</button>
            </section>
        @endif

        <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift">
            <p class="text-sm text-ink/65">{{ $workspace->name }} is asking for your approval on a change to <strong>{{ $project->title }}</strong>.</p>
            @unless($decision)
                <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight">{{ $revision->title }}</h1>
            @else
                <h2 class="mt-3 text-xl font-semibold leading-tight">{{ $revision->title }}</h2>
            @endunless
            <div class="mt-3 whitespace-pre-line text-ink/85">{{ $revision->description }}</div>
        </section>

        @if($revision->scope_reason || $revision->scope_excerpt || $revision->scopeItems->isNotEmpty())
            <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift">
                <h2 class="font-semibold">Why this is outside the agreed scope</h2>
                @if($revision->scope_reason)
                    <p class="mt-2 whitespace-pre-line text-ink/85">{{ $revision->scope_reason }}</p>
                @endif
                @if($revision->scopeItems->isNotEmpty() || $revision->scope_excerpt)
                    <div class="mt-4 rounded-xl border border-dashed border-ink/15 bg-paper p-4 text-sm">
                        <p class="font-medium text-ink/75">Original agreed scope</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-ink/75">
                            @foreach($revision->scopeItems as $item)
                                <li><span class="text-ink/55">{{ $item->type->label() }}:</span> {{ $item->title }}</li>
                            @endforeach
                        </ul>
                        @if($revision->scope_excerpt)
                            <p class="mt-2 whitespace-pre-line text-ink/75">{{ $revision->scope_excerpt }}</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift" aria-label="Impact">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/50">Price</dt>
                    <dd class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ $revision->price()->format() }}</dd>
                    <dd class="text-xs text-ink/55">{{ $revision->currency }}{{ $revision->price_minor === 0 ? ' · no charge' : '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/50">Timeline</dt>
                    <dd class="mt-1 font-medium">{{ $revision->timelineLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/50">Payment</dt>
                    <dd class="mt-1 font-medium">{{ $revision->payment_rule->label() }}</dd>
                </div>
            </dl>
            @if($revision->terms_note)
                <p class="mt-4 whitespace-pre-line border-t pt-3 text-sm text-ink/75">{{ $revision->terms_note }}</p>
            @endif
        </section>

        @if($payment && $payment->status->value !== 'not_required' && $decision && $decision->decision->value === 'approved')
            <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift" aria-labelledby="payment-heading">
                <h2 id="payment-heading" class="font-semibold">Payment</h2>
                <p class="mt-1 text-sm text-ink/65">Status: {{ $payment->status->label() }} · {{ $payment->amount()->format() }}</p>
                @if($revision->payment_instructions)
                    <p class="mt-2 whitespace-pre-line text-sm text-ink/85">{{ $revision->payment_instructions }}</p>
                @endif
                <p class="mt-2 text-xs text-ink/55">Payment goes directly to {{ $workspace->name }}. This page never handles your money.</p>
                @if($token && $payment->status->value === 'pending')
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        @if($payment->external_url)
                            <a href="{{ $route('client.pay') }}" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 font-medium text-white transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:brightness-110" style="background: {{ $brand }}">Pay {{ $payment->amount()->format() }}</a>
                        @endif
                        <form method="POST" action="{{ $route('client.payment-sent') }}" class="flex flex-1 flex-col gap-2 sm:flex-row">
                            @csrf
                            <label for="reference" class="sr-only">Payment reference (optional)</label>
                            <input id="reference" name="reference" maxlength="120" placeholder="Payment reference (optional)" class="flex-1 rounded-lg border border-ink/15 bg-white px-3 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-sm">
                            <button class="rounded-lg border border-ink/15 bg-white px-4 py-2 transition hover:border-ink/30 hover:bg-paper text-sm font-medium">I've sent the payment</button>
                        </form>
                    </div>
                @endif
            </section>
        @endif

        @if($canDecide)
            <section class="relative rounded-2xl border-2 bg-white p-6 card-lift" style="border-color: {{ $brand }}" aria-labelledby="approve-heading">
                <h2 id="approve-heading" class="text-xl font-semibold">Your decision</h2>
                <p class="mt-1 text-sm text-ink/60">Approving tells {{ $workspace->name }} they can start. You will get a copy for your records.</p>
                <form method="POST" action="{{ $route('client.approve') }}" class="mt-3 space-y-3" id="decision-form">
                    @csrf
                    <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                    <div class="hidden" aria-hidden="true"><label>Leave empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="client_name" class="text-sm font-medium">Your name</label>
                            <input id="client_name" name="client_name" required maxlength="120" value="{{ old('client_name', $changeRequest->recipient_name) }}" autocomplete="name" class="mt-1 w-full rounded-lg border border-ink/15 bg-white px-3 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        </div>
                        <div>
                            <label for="client_email" class="text-sm font-medium">Your email</label>
                            <input id="client_email" name="client_email" type="email" required maxlength="255" value="{{ old('client_email', $changeRequest->recipient_email) }}" autocomplete="email" class="mt-1 w-full rounded-lg border border-ink/15 bg-white px-3 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </div>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="accept_terms" value="1" class="mt-1" required>
                        <span>I approve this change, including the price of {{ $revision->price()->format() }}, the timeline impact and the payment condition above.</span>
                    </label>
                    <button class="w-full rounded-xl px-4 py-3.5 text-base font-semibold text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:brightness-110" style="background: {{ $brand }}">Approve change</button>
                </form>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-ink/75 transition-colors hover:text-red-700">Decline this change</summary>
                    <form method="POST" action="{{ $route('client.decline') }}" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                        <input type="hidden" name="client_name" value="{{ old('client_name', $changeRequest->recipient_name) }}" data-mirror="client_name">
                        <input type="hidden" name="client_email" value="{{ old('client_email', $changeRequest->recipient_email) }}" data-mirror="client_email">
                        <label for="reason" class="text-sm font-medium">Reason (optional)</label>
                        <textarea id="reason" name="reason" maxlength="2000" rows="3" class="w-full rounded-lg border border-ink/15 bg-white px-3 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"></textarea>
                        <button class="w-full rounded-lg border border-ink/15 bg-white px-4 py-2.5 font-medium transition hover:border-ink/30 hover:bg-paper">Decline change</button>
                    </form>
                </details>
            </section>

            <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift" aria-labelledby="question-heading">
                <h2 id="question-heading" class="font-semibold">Ask a question</h2>
                <form method="POST" action="{{ $route('client.comment') }}" class="mt-3 space-y-3">
                    @csrf
                    <div class="hidden" aria-hidden="true"><label>Leave empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <input type="hidden" name="client_name" value="{{ old('client_name', $changeRequest->recipient_name) }}" data-mirror="client_name">
                    <input type="hidden" name="client_email" value="{{ old('client_email', $changeRequest->recipient_email) }}" data-mirror="client_email">
                    <label for="body" class="sr-only">Your question</label>
                    <textarea id="body" name="body" required maxlength="5000" rows="3" placeholder="What would you like to know?" class="w-full rounded-lg border border-ink/15 bg-white px-3 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"></textarea>
                    <button class="rounded-lg border border-ink/15 bg-white px-4 py-2 transition hover:border-ink/30 hover:bg-paper text-sm font-medium">Send question</button>
                </form>
            </section>
        @endif

        @if($comments->isNotEmpty())
            <section class="rounded-2xl border border-ink/10 bg-white p-6 card-lift" aria-labelledby="conversation-heading">
                <h2 id="conversation-heading" class="font-semibold">Conversation</h2>
                <ol class="mt-3 space-y-3">
                    @foreach($comments as $comment)
                        <li class="rounded-lg {{ $comment->actor_type->value === 'client' ? 'bg-paper' : 'bg-brand-50' }} p-3 text-sm">
                            <p class="font-medium">{{ $comment->actor_name ?? ucfirst($comment->actor_type->value) }} <span class="font-normal text-ink/55">· {{ $comment->created_at?->copy()->setTimezone($tz)->format('j M Y, H:i') }}</span></p>
                            <p class="mt-1 whitespace-pre-line">{{ $comment->body }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <footer class="space-y-1 pb-8 text-center text-xs text-ink/55">
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
