@extends('layouts.prose', ['title' => 'Subprocessors', 'description' => 'Third parties that process personal data on our behalf.', 'updated' => '6 October 2026'])

@section('prose')
<p>We use the following subprocessors. We will update this page before adding a new subprocessor.</p>
<table>
    <thead><tr><th>Provider</th><th>Purpose</th><th>Location</th></tr></thead>
    <tbody>
        <tr><td>Paddle</td><td>Subscription billing, merchant of record, tax</td><td>UK / EU</td></tr>
        <tr><td>Cloud hosting provider</td><td>Application hosting, database, backups</td><td>EU or US (per deployment)</td></tr>
        <tr><td>Transactional email provider</td><td>Sending account and client emails</td><td>EU or US (per deployment)</td></tr>
        <tr><td>Error monitoring provider</td><td>Application error tracking (no client content)</td><td>EU or US (per deployment)</td></tr>
    </tbody>
</table>
@endsection
