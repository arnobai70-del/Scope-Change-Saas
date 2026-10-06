@extends('layouts.marketing', ['title' => 'Guides on scope creep and change requests', 'description' => 'Practical guides for freelancers and agencies on pricing and approving extra work.'])

@section('content')
<section class="mx-auto max-w-3xl px-4 pt-16">
    <h1 class="text-4xl font-semibold">Guides</h1>
    <div class="mt-10 divide-y divide-neutral-200">
        @foreach($posts as $slug => $post)
            <article class="py-6">
                <h2 class="text-xl font-semibold"><a href="{{ route('blog.post', $slug) }}" class="hover:underline">{{ $post['title'] }}</a></h2>
                <p class="mt-2 text-neutral-600">{{ $post['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>
@endsection
