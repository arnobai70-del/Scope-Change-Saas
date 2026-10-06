@extends('layouts.prose', ['title' => 'Free change request template', 'description' => 'A copy-ready change request template for freelancers and agencies, with every field you need.'])

@section('eyebrow', 'Template')

@section('prose')
<p>Copy this template into an email or document, or <a href="{{ route('tools.generator') }}">fill it in with the free generator</a>.</p>
<div class="rounded-lg border border-neutral-200 bg-neutral-50 p-5 font-mono text-sm whitespace-pre-line">Change request: CR-0001
Project: [project name]
Client: [client name]

Requested change
[Describe the extra work in plain language.]

Why this is outside the agreed scope
[Reference the original scope, e.g. "The agreed scope covered 5 pages; this adds a 6th."]

Price: [amount and currency]
Timeline impact: [+N working days / new delivery date]
Payment: [none / before work starts / before handoff]

To approve, reply "Approved" with your name, or use the approval link.
</div>
<h2>What to include</h2>
<ul>
    <li>A reference number you can quote on the invoice.</li>
    <li>The change in the client's language, so they recognise it.</li>
    <li>One sentence linking it to the agreed scope.</li>
    <li>The price, the currency and the timeline impact.</li>
    <li>When payment is due, if it is due before work starts.</li>
    <li>A clear way to approve, decline or ask a question.</li>
</ul>
@endsection
