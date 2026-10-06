@extends('layouts.prose', ['title' => 'How it works', 'description' => 'From agreed scope to approved change in four steps.'])

@section('prose')
<h2>1. Agree and lock the scope</h2>
<p>Create a project for your client and add what is included, what is excluded, how many revisions are allowed and what you need from the client. Lock it when the client has agreed.</p>

<h2>2. Turn an extra request into a change request</h2>
<p>When the client asks for something new, open a change request on the project. Paste their message, link it to the scope item it goes beyond, and add a price, timeline impact and payment condition.</p>

<h2>3. Send a secure approval link</h2>
<p>Preview exactly what your client will see, then send. The request is locked as revision 1. If you change anything later, a new revision is created and the old link stops working.</p>

<h2>4. Get a decision and keep the proof</h2>
<p>Your client approves, declines or asks a question without creating an account. If you require payment before start, the request waits until you confirm payment. When the work is done, generate a Proof Pack for your records.</p>

<p><a href="{{ route('register') }}">Start free</a> or <a href="{{ route('marketing.examples.client-approval') }}">see an example approval page</a>.</p>
@endsection
