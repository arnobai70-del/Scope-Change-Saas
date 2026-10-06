@extends('layouts.prose', ['title' => 'Security', 'description' => 'How we protect your workspace, your clients and your evidence.'])

@section('prose')
<h2>Workspace isolation</h2>
<p>Every record belongs to a workspace and every query is scoped to it. Access from another workspace returns "not found" rather than revealing that a record exists.</p>

<h2>Client approval links</h2>
<ul>
    <li>Links contain a long random token. Only a SHA-256 hash of the token is stored.</li>
    <li>Links expire, can be revoked at any time, and stop working when a new revision is sent.</li>
    <li>Approval pages are excluded from search engines and rate-limited.</li>
</ul>

<h2>Tamper-evident records</h2>
<p>Sent revisions are immutable. Activity is written to an append-only log where each entry includes the hash of the previous one, so any alteration is detectable.</p>

<h2>Accounts</h2>
<ul>
    <li>Passwords are hashed with bcrypt. Two-factor authentication is available for every account and required for platform administrators.</li>
    <li>Sensitive actions require recent password confirmation.</li>
    <li>You receive an email when your password or two-factor settings change.</li>
</ul>

<h2>Infrastructure</h2>
<ul>
    <li>All traffic is served over HTTPS with HSTS and strict security headers.</li>
    <li>Card payments are handled entirely by our payment provider. We never see or store card numbers.</li>
    <li>Database backups are encrypted and restore-tested.</li>
</ul>

<h2>Reporting a vulnerability</h2>
<p>Email security issues through our <a href="{{ route('contact') }}">contact page</a> with the subject "Security". We acknowledge reports within two business days.</p>
@endsection
