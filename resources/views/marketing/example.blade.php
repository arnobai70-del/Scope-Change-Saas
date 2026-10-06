@extends('layouts.prose', ['title' => 'Example: what your client sees', 'description' => 'A walkthrough of the no-login client approval page.'])

@section('prose')
<p>Your client receives an email with a single button. It opens a page like this on any device, without an account.</p>
<div class="not-prose overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 shadow-sm">
    <div class="flex items-center gap-3 border-b bg-white px-5 py-4">
        <div class="flex h-9 w-9 items-center justify-center rounded bg-indigo-600 text-sm font-semibold text-white">N</div>
        <div><p class="font-semibold text-neutral-900">Northwind Studio</p><p class="text-xs text-neutral-500">Change request CR-0007 · Revision 1</p></div>
    </div>
    <div class="space-y-4 p-5">
        <div class="rounded-lg border bg-white p-4">
            <p class="text-lg font-semibold text-neutral-900">Add a blog section to the website</p>
            <p class="mt-2 text-sm">A blog index, a post template and CMS setup for up to 10 posts.</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-neutral-500">Price</dt><dd class="text-xl font-semibold text-neutral-900">$1,200.00</dd></div>
                <div><dt class="text-neutral-500">Timeline</dt><dd class="font-medium text-neutral-900">+5 days</dd></div>
                <div><dt class="text-neutral-500">Payment</dt><dd class="font-medium text-neutral-900">Before work starts</dd></div>
            </dl>
        </div>
        <div class="rounded-lg border bg-white p-4 text-sm">
            <p class="font-medium text-neutral-900">Why this is extra</p>
            <p class="mt-1">The agreed scope covered five static pages. A blog with CMS was listed as excluded.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
            <span class="rounded-md bg-emerald-600 px-4 py-3 text-center text-sm font-medium text-white">Approve</span>
            <span class="rounded-md border px-4 py-3 text-center text-sm font-medium">Ask a question</span>
            <span class="rounded-md border px-4 py-3 text-center text-sm font-medium text-red-700">Decline</span>
        </div>
    </div>
</div>
<p>After approving, the client sees a receipt with the time of approval and the exact terms, which they can print or save.</p>
<p><a href="{{ route('register') }}">Create your first change request</a></p>
@endsection
