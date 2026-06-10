@props(['article', 'linkTitle' => false])

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @if ($article->imageUrl())
        <x-ui.lazy-image
            :src="$article->imageUrl()"
            :alt="$article->title"
            class="h-full w-full object-cover"
            wrapper-class="aspect-video w-full"
        />
    @else
        <div class="aspect-video bg-gradient-to-br from-brand/20 to-brand-dark/30"></div>
    @endif

    <div class="p-5">
        <time class="text-xs text-slate-500">{{ optional($article->published_at)->format('d/m/Y') }}</time>

        @if ($linkTitle)
            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                <a href="{{ route('landing.news.show', $article->slug) }}" class="hover:text-brand">{{ $article->title }}</a>
            </h2>
        @else
            <h3 class="mt-2 line-clamp-2 font-semibold text-slate-900">{{ $article->title }}</h3>
        @endif

        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 140) }}</p>

        {{ $slot }}
    </div>
</article>
