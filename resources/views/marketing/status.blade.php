@extends('layouts.prose', ['title' => 'System status', 'description' => 'Current service status.'])

@section('prose')
<div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
    <span class="h-3 w-3 rounded-full bg-emerald-500" aria-hidden="true"></span>
    <span>All systems operational</span>
</div>
<p>Incidents and maintenance windows are posted here. For a machine-readable health check, monitoring services can call <code>/up</code>.</p>
<table>
    <tr><th>Web app</th><td>Operational</td></tr>
    <tr><th>Client approval pages</th><td>Operational</td></tr>
    <tr><th>Email delivery</th><td>Operational</td></tr>
    <tr><th>Background jobs</th><td>Operational</td></tr>
</table>
@endsection
