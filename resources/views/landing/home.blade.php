@extends('layouts.landing')

@section('title', __('messages.home').' — '.config('landing.brand_name', 'Shopify'))

@push('meta')
@php
    $heroLcp = asset('images/landing/'.(config('landing.hero_slides.0.image') ?? 'hero/TG11.png'));
@endphp
<link rel="preload" as="image" href="{{ $heroLcp }}" fetchpriority="high">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('landing.brand_name', 'Shopify'),
    'url' => url('/'),
    'description' => __('landing.meta_description'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
    <x-landing.hero-carousel :banners="$banners" />
    <x-landing.text-marquee />
    <x-landing.feature-grid />
    <x-landing.opportunity-carousel />
@endsection
