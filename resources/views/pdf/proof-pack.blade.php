@php
    /** @var array<string, mixed> $content */
    $cover = $content['cover'];
    $label = fn (string $event) => \App\Support\Presenters::eventLabel($event);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Proof Pack {{ $cover['reference'] }} v{{ $proofPack->version }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.45; }
        h1 { font-size: 20pt; margin: 0 0 4pt; }
        h2 { font-size: 13pt; margin: 18pt 0 6pt; border-bottom: 1px solid #d1d5db; padding-bottom: 3pt; }
        h3 { font-size: 11pt; margin: 12pt 0 4pt; }
        table { width: 100%; border-collapse: collapse; margin: 4pt 0 8pt; }
        th, td { text-align: left; vertical-align: top; padding: 4pt 6pt; border-bottom: 1px solid #e5e7eb; }
        th { width: 32%; color: #4b5563; font-weight: normal; }
        .muted { color: #6b7280; }
        .small { font-size: 8pt; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 7.5pt; word-break: break-all; }
        .badge { display: inline-block; padding: 1pt 6pt; border-radius: 3pt; background: #eef2ff; color: #3730a3; font-size: 9pt; }
        .approved { background: #ecfdf5; color: #065f46; }
        .declined { background: #fef2f2; color: #991b1b; }
        .page-break { page-break-before: always; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 7.5pt; color: #6b7280; }
        .box { border: 1px solid #e5e7eb; padding: 6pt 8pt; margin: 4pt 0; }
    </style>
</head>
<body>
<footer>
    Proof Pack {{ $cover['reference'] }} · version {{ $proofPack->version }} · generated {{ $generatedAt->toIso8601String() }} · content SHA-256 {{ $contentHash }}
</footer>

<p class="muted small">{{ $cover['workspace'] }}</p>
<h1>Change Request Proof Pack</h1>
<p><span class="badge">{{ $cover['status'] }}</span></p>

<table>
    <tr><th>Reference</th><td>{{ $cover['reference'] }}</td></tr>
    <tr><th>Project</th><td>{{ $cover['project'] }}@if($cover['project_code']) ({{ $cover['project_code'] }})@endif</td></tr>
    <tr><th>Client</th><td>{{ $cover['client'] }}</td></tr>
    <tr><th>Prepared by</th><td>{{ $cover['workspace'] }}</td></tr>
    <tr><th>Times shown in</th><td>{{ $cover['timezone'] }} (UTC alongside)</td></tr>
    <tr><th>Pack version</th><td>{{ $proofPack->version }}</td></tr>
</table>

<p class="small muted">
    This document is an evidence record generated from locked, immutable revisions and an append-only, hash-chained activity log.
    It is not legal advice and does not by itself constitute a contract.
</p>

<h2>Agreed baseline scope</h2>
@if($content['baseline'])
    <p><strong>{{ $content['baseline']['title'] }}</strong> · version {{ $content['baseline']['version'] }} · locked {{ $content['baseline']['locked_at']['local'] ?? 'n/a' }}</p>
    <table>
        @foreach($content['baseline']['items'] as $item)
            <tr>
                <th>{{ ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? 'item'))) }}</th>
                <td>{{ $item['title'] ?? '' }}@if(!empty($item['detail']))<br><span class="muted small">{{ $item['detail'] }}</span>@endif</td>
            </tr>
        @endforeach
    </table>
    @if($content['baseline']['content_hash'])<p class="mono">Baseline hash: {{ $content['baseline']['content_hash'] }}</p>@endif
@else
    <p class="muted">No locked baseline was recorded for this project.</p>
@endif

@foreach($content['revisions'] as $revision)
    @php $snap = $revision['snapshot'] ?? []; @endphp
    <h2>Revision {{ $revision['revision_no'] }}: {{ $snap['title'] ?? '' }}</h2>
    <table>
        <tr><th>Locked at</th><td>{{ $revision['locked_at']['local'] ?? '' }} <span class="muted small">{{ $revision['locked_at']['utc'] ?? '' }}</span></td></tr>
        <tr><th>Requested change</th><td>{!! nl2br(e((string) ($snap['description'] ?? ''))) !!}</td></tr>
        <tr><th>Why it is outside scope</th><td>{!! nl2br(e((string) ($snap['scope_reason'] ?? ''))) !!}</td></tr>
        @if(!empty($snap['scope_excerpt']))<tr><th>Original scope reference</th><td>{!! nl2br(e((string) $snap['scope_excerpt'])) !!}</td></tr>@endif
        @if(!empty($snap['scope_items']))
            <tr><th>Linked scope items</th><td>@foreach($snap['scope_items'] as $item){{ $item['title'] }}@if(!$loop->last), @endif @endforeach</td></tr>
        @endif
        <tr><th>Price</th><td><strong>{{ $snap['price']['formatted'] ?? '' }}</strong> ({{ $snap['price']['currency'] ?? '' }})</td></tr>
        <tr><th>Timeline impact</th><td>{{ $snap['timeline']['label'] ?? '' }}</td></tr>
        <tr><th>Payment condition</th><td>{{ \App\Enums\PaymentRule::tryFrom((string) ($snap['payment_rule'] ?? 'none'))?->label() }}</td></tr>
        @if(!empty($snap['terms_note']))<tr><th>Terms</th><td>{!! nl2br(e((string) $snap['terms_note'])) !!}</td></tr>@endif
    </table>
    @if($revision['decision'])
        @php $d = $revision['decision']; @endphp
        <div class="box">
            <span class="badge {{ $d['decision'] }}">{{ ucfirst($d['decision']) }}</span>
            by {{ $d['client_name'] }} &lt;{{ $d['client_email'] }}&gt;
            on {{ $d['decided_at']['local'] ?? '' }} <span class="muted small">({{ $d['decided_at']['utc'] ?? '' }})</span>
            @if($d['accepted_terms_version'])<br><span class="small muted">Accepted terms version {{ $d['accepted_terms_version'] }}</span>@endif
            @if($d['reason'])<br><span class="small">Reason: {{ $d['reason'] }}</span>@endif
        </div>
    @else
        <p class="muted small">No client decision recorded for this revision.</p>
    @endif
    <p class="mono">Snapshot hash: {{ $revision['snapshot_hash'] }}</p>
@endforeach

@if(count($content['payments']))
    <h2>Payment</h2>
    <table>
        @foreach($content['payments'] as $payment)
            <tr>
                <th>{{ $payment['amount'] }}</th>
                <td>
                    {{ $payment['status'] }}
                    @if($payment['reference']) · ref {{ $payment['reference'] }}@endif
                    @if($payment['marked_sent_at'])<br><span class="small muted">Client marked sent {{ $payment['marked_sent_at']['local'] }}</span>@endif
                    @if($payment['confirmed_at'])<br><span class="small muted">Confirmed {{ $payment['confirmed_at']['local'] }}</span>@endif
                </td>
            </tr>
        @endforeach
    </table>
    <p class="small muted">Payments are made directly to the service provider. This platform does not process client funds.</p>
@endif

@if($content['completion']['started_at'] || $content['completion']['completed_at'])
    <h2>Delivery</h2>
    <table>
        @if($content['completion']['started_at'])<tr><th>Work started</th><td>{{ $content['completion']['started_at']['local'] }}</td></tr>@endif
        @if($content['completion']['completed_at'])<tr><th>Completed</th><td>{{ $content['completion']['completed_at']['local'] }}</td></tr>@endif
    </table>
@endif

@if(count($content['comments']))
    <h2>Shared conversation</h2>
    @foreach($content['comments'] as $comment)
        <div class="box">
            <strong>{{ $comment['author'] }}</strong> <span class="muted small">{{ $comment['at']['local'] ?? '' }}</span><br>
            {!! nl2br(e($comment['body'])) !!}
        </div>
    @endforeach
@endif

<div class="page-break"></div>
<h2>Activity log</h2>
<p class="small muted">Each entry is chained to the previous one with SHA-256. Altering any entry breaks every later hash.</p>
<table>
    @foreach($content['timeline'] as $event)
        <tr>
            <th>{{ $event['at']['local'] ?? '' }}</th>
            <td>{{ $label($event['event']) }} <span class="muted">· {{ $event['actor'] }}</span><br><span class="mono">{{ $event['hash'] }}</span></td>
        </tr>
    @endforeach
</table>
</body>
</html>
