@props([
    'title' => \App\Support\SiteSettings::websiteTitle(),
    'description' => null,
    'image' => null,
    'type' => 'website',
    'url' => null,
    'jsonLd' => null,
])

@php
    $description = $description ?? \App\Support\SiteSettings::seoDescription();
    $url = $url ?? url()->current();
    $image = $image ?? \App\Support\SiteSettings::seoOgImageUrl();
    $siteName = \App\Support\SiteSettings::websiteTitle();
@endphp

<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $url }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta property="og:image" content="{{ $image }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

@if ($jsonLd)
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
