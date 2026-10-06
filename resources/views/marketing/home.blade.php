@extends('layouts.marketing', ['title' => 'Turn scope creep into approved change revenue', 'description' => 'Create a priced change request in minutes, send a no-login approval link, and keep an audit trail and PDF Proof Pack for every extra request.'])

@section('content')
<section class="mx-auto max-w-6xl px-4 pb-16 pt-16 text-center sm:pt-24">
    <p class="text-sm font-medium text-indigo-600">For freelancers, agencies and consultants</p>
    <h1 class="mx-auto mt-3 max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">Turn scope creep into approved change revenue before work starts.</h1>
    <p class="mx-auto mt-5 max-w-2xl text-lg text-neutral-600">Compare each extra request with the agreed scope, send a priced change request your client approves in one click, and keep timestamped proof of every decision.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-500">Start free, no card needed</a>
        <a href="{{ route('marketing.examples.client-approval') }}" class="rounded-md border border-neutral-300 px-5 py-3 font-medium hover:bg-neutral-50">See the client view</a>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4">
    <div class="grid gap-6 md:grid-cols-3">
        @foreach([
            ['1. Lock the agreed scope', 'Record deliverables, exclusions, revision allowance and assumptions once. Lock it so both sides share one version of the truth.'],
            ['2. Price the extra request', 'Paste the client message, link it to the original scope, add price, timeline impact and a payment condition.'],
            ['3. Get a clear approval', 'Your client opens a branded page, no login needed, and approves, declines or asks a question. You get notified instantly.'],
        ] as [$heading, $text])
            <div class="rounded-xl border border-neutral-200 p-6">
                <h2 class="font-semibold">{{ $heading }}</h2>
                <p class="mt-2 text-sm text-neutral-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto mt-20 max-w-6xl px-4">
    <h2 class="text-center text-2xl font-semibold">Everything you need to stop working for free</h2>
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['Immutable revisions', 'Once sent, a request is locked. Any change creates a new revision, so approvals always match exactly what the client saw.'],
            ['Automatic reminders', 'Polite reminders after 24 and 72 hours and before expiry. They stop the moment your client decides.'],
            ['Payment before work', 'Optionally require payment before work starts, using your own payment link. We never touch client funds.'],
            ['Proof Packs', 'One PDF with the baseline, revisions, approval, payment status and a hash-chained activity log.'],
            ['Multi-currency', '25 currencies with correct decimals, so a ¥ request never looks like a $ one.'],
            ['Revenue reporting', 'See approved change revenue, approval rate and time to decision across every project.'],
        ] as [$heading, $text])
            <div>
                <h3 class="font-medium">{{ $heading }}</h3>
                <p class="mt-1 text-sm text-neutral-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto mt-20 max-w-6xl px-4">
    <h2 class="text-center text-2xl font-semibold">Simple pricing</h2>
    <p class="mt-2 text-center text-neutral-600">One approved change request usually pays for a year.</p>
    <div class="mt-8">@include('marketing.partials.plans')</div>
</section>

<section class="mx-auto mt-20 max-w-3xl px-4">
    <h2 class="text-center text-2xl font-semibold">Questions</h2>
    <dl class="mt-6 divide-y divide-neutral-200">
        @foreach([
            ['Does my client need an account?', 'No. They receive a secure link and can approve on any phone or laptop. The link expires and can be revoked at any time.'],
            ['Is an approval legally binding?', 'We record who approved what, when, and the exact terms they saw. Whether that forms a binding agreement depends on your contract and jurisdiction. This is not legal advice.'],
            ['Do you process my client payments?', 'No. You add your own payment link (Stripe, PayPal, bank details). We only record whether payment was marked sent and confirmed.'],
            ['Can I cancel anytime?', 'Yes. Cancel from billing settings and keep access until the end of the paid period. Your data stays exportable.'],
        ] as [$q, $a])
            <div class="py-4">
                <dt class="font-medium">{{ $q }}</dt>
                <dd class="mt-1 text-sm text-neutral-600">{{ $a }}</dd>
            </div>
        @endforeach
    </dl>
</section>
@endsection
