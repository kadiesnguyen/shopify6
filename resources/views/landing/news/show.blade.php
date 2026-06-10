@extends('layouts.landing')

@section('title', $article->title.' — '.config('landing.brand_name', 'Shopify'))
@section('meta_description', $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 160))

@push('meta')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article->title,
    'datePublished' => optional($article->published_at)->toAtomString(),
    'dateModified' => $article->updated_at?->toAtomString(),
    'description' => $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 160),
    'url' => route('landing.news.show', $article->slug),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 md:py-14">
        <a href="{{ route('landing.news.index') }}" class="text-sm font-semibold text-brand hover:text-brand-dark">← {{ __('landing.news.title') }}</a>

        <header class="mt-4">
            <time class="text-sm text-slate-500">{{ optional($article->published_at)->format('d/m/Y') }}</time>
            <h1 class="mt-2 text-3xl font-bold text-slate-900 md:text-4xl">{{ $article->title }}</h1>
        </header>

        @if ($article->imageUrl())
            <x-ui.lazy-image
                :src="$article->imageUrl()"
                :alt="$article->title"
                class="h-full w-full rounded-2xl object-cover"
                wrapper-class="mt-8 aspect-video w-full overflow-hidden rounded-2xl"
                eager
            />
        @endif

        <div class="prose prose-slate mt-8 max-w-none">
            {!! $article->content !!}
        </div>
    </article>
@endsection
