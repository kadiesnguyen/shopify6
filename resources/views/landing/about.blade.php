@extends('layouts.landing')

@section('title', __('landing.about.title').' — '.config('landing.brand_name', 'Shopify'))

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 md:py-14">
        <h1 class="text-3xl font-bold text-brand-dark">
            {{ $page?->translate('title') ?? __('landing.about.title') }}
        </h1>

        <div class="prose prose-slate mt-8 max-w-none">
            @if ($page)
                {!! $page->translate('content') !!}
            @else
                <p>{{ __('landing.features.intro') }}</p>
            @endif
        </div>
    </section>
@endsection
