<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteTitle = \App\Support\SiteSettings::websiteTitle();
        $pageTitle = trim($__env->yieldContent('title') ?: $siteTitle);
    @endphp
    <title>{{ $pageTitle }}</title>
    <link rel="icon" href="{{ \App\Support\SiteSettings::faviconUrl() }}">
    <x-seo.meta
        :title="$pageTitle"
        :description="trim($__env->yieldContent('meta_description') ?: \App\Support\SiteSettings::seoDescription())"
        :image="\App\Support\SiteSettings::seoOgImageUrl()"
        :url="url()->current()"
    />
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-layout-root overflow-x-hidden bg-white text-slate-900 antialiased">
    <x-landing.header />

    <main class="min-w-0 max-w-full overflow-x-hidden">
        @if (session('status'))
            <div class="mx-auto max-w-6xl px-4 pt-4">
                <x-ui.alert type="success" :message="session('status')" />
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto max-w-6xl px-4 pt-4">
                <x-ui.error-state class="text-left">
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.error-state>
            </div>
        @endif

        @yield('content')
    </main>

    <x-landing.footer />
</body>
</html>
