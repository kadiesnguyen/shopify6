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
                    <x-landing.news-card :article="$article">
                        <a href="{{ route('landing.news.show', $article->slug) }}" class="mt-4 inline-flex text-sm font-semibold text-brand hover:text-brand-dark">
                            {{ __('landing.news.read_more') }} →
                        </a>
                    </x-landing.news-card>
                @endforeach
            </div>
        @endif
    </div>
</section>
