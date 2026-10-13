@extends('layouts.marketing', ['title' => 'How it works', 'description' => 'From agreed scope to approved change in four steps.'])

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'How it works',
    'heading' => 'From agreed scope to <span class="marker">approved change</span> in four steps.',
    'intro' => 'No new habits for your client. They get one link, make one decision, and you both keep the same record.',
])

<section class="mx-auto mt-16 max-w-5xl px-4">
    <ol class="relative space-y-10 before:absolute before:bottom-10 before:left-6 before:top-10 before:w-0.5 before:bg-gradient-to-b before:from-brand-600 before:via-brand-300 before:to-lime-500 sm:before:left-8">
        @foreach([
            ['Agree and lock the scope', 'Create a project for your client and add what is included, what is excluded, how many revisions are allowed and what you need from the client. Lock it when the client has agreed.', ['Included', 'Excluded', '2 revisions', 'Locked']],
            ['Turn an extra request into a change request', 'When the client asks for something new, open a change request on the project. Paste their message, link it to the scope item it goes beyond, and add a price, timeline impact and payment condition.', ['Client message', 'Price', '+4 days', 'Before start']],
            ['Send a secure approval link', 'Preview exactly what your client will see, then send. The request is locked as revision 1. If you change anything later, a new revision is created and the old link stops working.', ['Preview', 'Revision 1', 'Expiring link']],
            ['Get a decision and keep the proof', 'Your client approves, declines or asks a question without creating an account. If you require payment before start, the request waits until you confirm payment. When the work is done, generate a Proof Pack for your records.', ['Approved', 'Payment confirmed', 'Proof Pack']],
        ] as $i => [$heading, $text, $chips])
            <li class="group relative flex gap-5 sm:gap-8">
                <span @class([
                    'relative z-10 flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl font-display text-lg font-bold shadow-lg transition duration-300 group-hover:-rotate-6 group-hover:scale-110 sm:h-16 sm:w-16 sm:text-2xl',
                    'bg-ink text-signal' => $i === 3,
                    'bg-brand-600 text-white' => $i !== 3,
                ])>{{ $i + 1 }}</span>
                <div @class([
                    'hover-lift flex-1 rounded-3xl border p-6 sm:p-8',
                    'border-ink bg-ink text-white' => $i === 3,
                    'border-ink/10 bg-white' => $i !== 3,
                ])>
                    <h2 class="text-2xl font-semibold tracking-tight">{{ $heading }}</h2>
                    <p @class(['mt-3 leading-relaxed', 'text-white/70' => $i === 3, 'text-ink/70' => $i !== 3])>{{ $text }}</p>
                    <div class="mt-5 flex flex-wrap gap-2" aria-hidden="true">
                        @foreach($chips as $chip)
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-semibold',
                                'bg-signal text-ink' => $i === 3,
                                'bg-paper text-ink/70' => $i !== 3,
                            ])>{{ $chip }}</span>
                        @endforeach
                    </div>
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-14 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('register') }}" class="group btn-primary px-5 py-3 text-base">Start free <span class="nudge" aria-hidden="true">→</span></a>
        <a href="{{ route('marketing.examples.client-approval') }}" class="btn-outline px-5 py-3 text-base">See an example approval page</a>
    </div>
</section>
@endsection
