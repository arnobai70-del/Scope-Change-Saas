@extends('layouts.prose', ['title' => 'Help docs', 'description' => 'Short answers to the most common questions.'])

@section('prose')
<h2>Getting started</h2>
<p>Sign up, confirm your email, then follow the three-step onboarding: workspace details, your first client, and your first project. You can send your first change request straight after.</p>

<h2>What happens when I edit a sent request?</h2>
<p>Sent requests are locked. Choosing "Revise" creates a new draft revision. When you send it, the previous link stops working and your client receives a fresh link for the new revision.</p>

<h2>My client says the link does not work</h2>
<p>Links expire after the window you chose and stop working after a new revision is sent or the link is revoked. Open the request and choose "Resend" to issue a fresh link.</p>

<h2>How do payment conditions work?</h2>
<p>If you choose "Payment before start", an approved request moves to "Payment pending". Your client can open your payment link and mark payment as sent. You confirm when the money arrives, and the request becomes "Ready to start".</p>

<h2>Can I stop reminders?</h2>
<p>Reminders stop automatically when the client decides. Clients can also mute reminders from the link in any reminder email.</p>

<h2>Exporting and deleting data</h2>
<p>Owners can export all workspace data as JSON from Settings, Data. Account deletion is available under Settings, Profile.</p>

<p>Still stuck? <a href="{{ route('contact') }}">Contact support</a>.</p>
@endsection
