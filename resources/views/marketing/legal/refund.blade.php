@extends('layouts.prose', ['title' => 'Refund Policy', 'updated' => '6 October 2026', 'description' => 'How refunds work for subscriptions.'])

@section('prose')
<p>Every account starts on a free plan or a free trial, so you can try the product before paying.</p>
<h2>Monthly plans</h2>
<p>You can cancel at any time and keep access until the end of the current month. We do not refund partial months.</p>
<h2>Yearly plans</h2>
<p>If you request a refund within 14 days of a new yearly charge, we will refund it in full. After that, you keep access until the end of the year.</p>
<h2>Statutory rights</h2>
<p>Nothing here limits rights you have under consumer law in your country.</p>
<h2>How to request</h2>
<p>Refunds are processed by our merchant of record, Paddle. <a href="{{ route('contact') }}">Contact us</a> or reply to your receipt email.</p>
@endsection
