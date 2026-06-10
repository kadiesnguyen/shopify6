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
                    <x-landing.news-card :article="$article" link-title />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $news->links() }}
            </div>
        @endif
    </section>
@endsection
