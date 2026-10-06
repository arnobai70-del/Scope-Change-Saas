@extends('layouts.prose', ['title' => 'Data Processing Addendum', 'updated' => '6 October 2026', 'description' => 'Our commitments as your data processor.'])

@section('prose')
<p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Template addendum. Review with counsel before launch.</p>
<h2>Scope</h2>
<p>This addendum applies when we process personal data of your clients and contacts on your behalf.</p>
<h2>Our commitments</h2>
<ul>
    <li>Process personal data only on your documented instructions, which are your use of the service.</li>
    <li>Ensure personnel are bound by confidentiality.</li>
    <li>Apply appropriate technical and organisational security measures, described on our <a href="{{ route('marketing.security') }}">security page</a>.</li>
    <li>Use only the listed <a href="{{ route('marketing.subprocessors') }}">subprocessors</a> and give notice before adding new ones.</li>
    <li>Assist with data subject requests through in-app export and deletion.</li>
    <li>Notify you without undue delay of a personal data breach affecting your data.</li>
    <li>Delete or return personal data at the end of the service.</li>
</ul>
<h2>Transfers</h2>
<p>Where required, the EU standard contractual clauses and the UK addendum are incorporated by reference.</p>
@endsection
