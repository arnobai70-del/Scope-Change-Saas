@extends('layouts.marketing', ['title' => 'Guides on scope creep and change requests', 'description' => 'Practical guides for freelancers and agencies on pricing and approving extra work.'])

@section('content')
@include('marketing.partials.page-hero', [
    'eyebrow' => 'Guides',
    'heading' => 'Practical guides on <span class="marker">pricing extra work</span>.',
    'intro' => 'Practical guides for freelancers and agencies on pricing and approving extra work.',
])

<section class="mx-auto mt-12 max-w-6xl px-4">
    <div class="grid gap-5 md:grid-cols-3">
        @foreach($posts as $slug => $post)
            @php($tone = [['bg-brand-600 text-white', 'text-white/70', 'bg-white/15 text-white'], ['bg-white border border-ink/10', 'text-ink/65', 'bg-brand-50 text-brand-700'], ['bg-signal-soft border border-lime-300', 'text-ink/65', 'bg-white text-ink'], ][$loop->index % 3])
            <article class="group hover-lift relative flex flex-col rounded-3xl p-7 {{ $tone[0] }}">
                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $tone[2] }}">Guide {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <h2 class="mt-5 text-xl font-semibold leading-snug"><a href="{{ route('blog.post', $slug) }}" class="after:absolute after:inset-0">{{ $post['title'] }}</a></h2>
                <p class="mt-3 flex-1 text-sm leading-relaxed {{ $tone[1] }}">{{ $post['description'] }}</p>
                <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold" aria-hidden="true">Read guide <span class="nudge">→</span></span>
            </article>
        @endforeach
    </div>
</section>
@endsection
