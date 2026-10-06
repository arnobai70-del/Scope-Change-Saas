@extends('layouts.prose', ['title' => 'Features', 'description' => 'Scope baselines, priced change requests, no-login client approvals, reminders, payment conditions, Proof Packs and reporting.'])

@section('prose')
<p>Everything in the product exists for one outcome: extra work gets priced and approved before anyone starts it.</p>

<h2>Scope baselines</h2>
<p>Record deliverables, exclusions, revision allowances, assumptions and client responsibilities for each project. Lock a baseline to freeze it with a content hash. Editing a locked baseline creates a new version instead of rewriting history.</p>

<h2>Change requests</h2>
<ul>
    <li>Paste the client's original message and link the request to the scope items it extends.</li>
    <li>Price in 25 currencies, with correct decimals for each one.</li>
    <li>State the timeline impact in days or with a new delivery date.</li>
    <li>Choose a payment condition: none, before start, before handoff, or custom terms.</li>
    <li>Start from built-in templates or your own.</li>
</ul>

<h2>No-login client approval</h2>
<p>Your client receives a branded, mobile-friendly page through a secure, expiring link. They can approve, decline with a reason, or ask a question. Approvals record name, email, timestamp and the exact revision they saw.</p>

<h2>Reminders and notifications</h2>
<p>Automatic reminders go out at 24 hours, 72 hours and 24 hours before expiry, and stop the moment the client decides. You are notified when the client opens, approves, declines or asks a question.</p>

<h2>Proof Packs</h2>
<p>Generate one PDF per change request with the baseline, every locked revision, the client decision, payment status, conversation and a hash-chained activity log.</p>

<h2>Reporting</h2>
<p>See approved change revenue by currency, approval rate, average time to decision, and the projects that generate the most extra requests.</p>

<h2>Teams</h2>
<p>Invite teammates with owner, admin or member roles on the Agency plan. Every action is attributed in the audit trail.</p>
@endsection
