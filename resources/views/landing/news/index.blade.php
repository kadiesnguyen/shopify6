@extends('layouts.landing')

@section('title', __('landing.news.title').' — '.config('landing.brand_name', 'Shopify'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 md:py-14">
        <h1 class="text-3xl font-bold text-brand-dark">{{ __('landing.news.title') }}</h1>

        @if ($news->isEmpty())
            <x-ui.empty-state :title="__('landing.news.empty')" class="mt-10" />
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $article)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="aspect-video bg-gradient-to-br from-brand/20 to-brand-dark/30"></div>
                        <div class="p-5">
                            <time class="text-xs text-slate-500">{{ optional($article->published_at)->format('d/m/Y') }}</time>
                            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                                <a href="{{ route('landing.news.show', $article->slug) }}" class="hover:text-brand">{{ $article->title }}</a>
                            </h2>
                            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 140) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $news->links() }}
            </div>
        @endif
    </section>
@endsection
