@props(['newsItems' => collect()])

<section class="bg-slate-50 py-14 md:py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="flex items-end justify-between gap-4">
            <h2 class="text-2xl font-bold text-brand-dark md:text-3xl">{{ __('landing.news.title') }}</h2>
            <a href="{{ route('landing.news.index') }}" class="text-sm font-semibold text-brand hover:text-brand-dark">{{ __('landing.news.view_all') }} →</a>
        </div>

        @if ($newsItems->isEmpty())
            <p class="mt-8 text-center text-slate-500">{{ __('landing.news.empty') }}</p>
        @else
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($newsItems as $article)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="aspect-video bg-gradient-to-br from-brand/20 to-brand-dark/30"></div>
                        <div class="p-5">
                            <time class="text-xs text-slate-500">{{ optional($article->published_at)->format('d/m/Y') }}</time>
                            <h3 class="mt-2 line-clamp-2 font-semibold text-slate-900">{{ $article->title }}</h3>
                            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}</p>
                            <a href="{{ route('landing.news.show', $article->slug) }}" class="mt-4 inline-flex text-sm font-semibold text-brand hover:text-brand-dark">
                                {{ __('landing.news.read_more') }} →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
