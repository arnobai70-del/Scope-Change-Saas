@extends('layouts.prose', ['title' => 'Privacy Policy', 'updated' => '6 October 2026', 'description' => 'What personal data we collect and how we use it.'])

@section('prose')
<p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Template policy. Review with counsel before launch.</p>
<h2>Who we are</h2>
<p>We are the controller of account data (your name, email, login activity, billing status). For the content you store about your clients, we act as your processor under our <a href="{{ route('legal', 'dpa') }}">Data Processing Addendum</a>.</p>
<h2>What we collect</h2>
<ul>
    <li>Account details: name, email, password hash, two-factor settings, timezone.</li>
    <li>Workspace content: clients, projects, scope, change requests, comments.</li>
    <li>Client approval records: name, email, decision, timestamp, and for security a truncated IP address and user agent.</li>
    <li>Product usage events without content, used to improve the product.</li>
    <li>Billing status from our payment provider. We never receive card numbers.</li>
</ul>
<h2>How we use it</h2>
<p>To provide the service, send transactional emails, secure accounts, prevent abuse and comply with law. We do not sell personal data and do not use your content to train AI models.</p>
<h2>Retention</h2>
<p>Workspace content is kept while your account is active. IP addresses on client decisions are removed after the configured retention period. Deleted accounts are purged after a short grace period, except where we must keep records by law.</p>
<h2>Your rights</h2>
<p>You can access, correct, export and delete your data from the app, or contact us. You may complain to your local data protection authority.</p>
<h2>International transfers</h2>
<p>Where data leaves your region, we rely on appropriate safeguards such as standard contractual clauses. See our <a href="{{ route('marketing.subprocessors') }}">subprocessors</a>.</p>
<h2>Cookies</h2>
<p>We use only essential cookies for sessions, security and remembering your preferences. We do not use advertising cookies.</p>
@endsection
