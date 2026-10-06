@php
$paymentText = ['none' => null, 'before_start' => 'Payment is due before work starts.', 'before_handoff' => 'Payment is due before final handoff.'][$payment] ?? null;
@endphp
<div class="space-y-3 whitespace-pre-line text-sm leading-relaxed">Hi {{ $client }},

Thanks for the request. To keep {{ $project }} on track, here is a short change request for your approval.

<strong>Requested change</strong>
{{ $change }}

@if($original)<strong>Why this is outside the agreed scope</strong>
{{ $original }}

@endif<strong>Price:</strong> {{ $price ?? 'to be confirmed' }}
<strong>Timeline impact:</strong> {{ $days > 0 ? '+'.$days.' '.\Illuminate\Support\Str::plural('day', $days) : 'No change to the delivery date' }}
@if($paymentText)<strong>Payment:</strong> {{ $paymentText }}
@endif
If you'd like to go ahead, reply "Approved" and I'll schedule it. Happy to answer any questions.</div>
