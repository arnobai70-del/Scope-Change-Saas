@extends('layouts.marketing', ['title' => 'Contact', 'description' => 'Questions about the product, billing or security.'])

@section('content')
<section class="mx-auto max-w-xl px-4 pt-16">
    <h1 class="text-3xl font-semibold">Contact us</h1>
    <p class="mt-2 text-neutral-600">We reply by email, usually within one business day.</p>
    <form method="POST" action="{{ route('contact.submit') }}" class="mt-8 space-y-4">
        @csrf
        <div class="hidden" aria-hidden="true"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        @foreach([['name', 'Your name', 'text', 'name'], ['email', 'Email', 'email', 'email'], ['subject', 'Subject', 'text', 'off']] as [$field, $label, $type, $auto])
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $auto }}" value="{{ old($field) }}" required class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2">
                @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <div>
            <label for="body" class="block text-sm font-medium">Message</label>
            <textarea id="body" name="body" rows="6" required maxlength="5000" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2">{{ old('body') }}</textarea>
            @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-500">Send message</button>
    </form>
</section>
@endsection
