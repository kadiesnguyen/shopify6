<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteTitle = \App\Support\SiteSettings::websiteTitle();
        $pageTitle = trim($__env->yieldContent('title') ?: __('messages.member_portal'));
    @endphp
    <title>{{ $pageTitle }} — {{ $siteTitle }}</title>
    <link rel="icon" href="{{ \App\Support\SiteSettings::faviconUrl() }}">
    <x-seo.meta
        :title="$pageTitle.' — '.$siteTitle"
        :description="\App\Support\SiteSettings::seoDescription()"
        :image="\App\Support\SiteSettings::seoOgImageUrl()"
        :url="url()->current()"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-app bg-gray-100 text-base text-gray-900 antialiased">
    <div class="portal-shell mx-auto min-h-dvh w-full bg-white md:max-w-[420px] md:shadow-xl md:ring-1 md:ring-gray-200">
        @unless(View::hasSection('hide_portal_header'))
            <x-member.header />
        @endunless

        <main @class([
            'min-w-0 flex flex-col',
            'min-h-[calc(100dvh-4.5rem)] pb-[calc(4.5rem+env(safe-area-inset-bottom))]' => ! View::hasSection('portal_chat_page'),
            'h-dvh max-h-dvh overflow-hidden pb-0' => View::hasSection('portal_chat_page'),
            'pt-[3.75rem]' => ! View::hasSection('hide_portal_header'),
            'bg-gray-50' => View::hasSection('portal_gray_bg') || View::hasSection('portal_chat_page'),
            'bg-white' => ! View::hasSection('portal_gray_bg') && ! View::hasSection('portal_chat_page'),
        ])>
            @hasSection('back_url')
                <div class="px-4 pt-2">
                    <a href="@yield('back_url')" class="inline-flex items-center gap-1 text-base text-gray-600 no-underline hover:text-emerald-600">
                        <x-member.icon name="chevron-left" class="size-4" />
                        {{ __('member.back') }}
                    </a>
                </div>
            @endif

            <div @class([
                'px-4' => ! View::hasSection('full_bleed'),
                'flex min-h-0 flex-1 flex-col' => View::hasSection('portal_chat_page'),
            ])>
                @if (session('status'))
                    <x-ui.alert type="success" :message="session('status')" class="mb-4" />
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>

    <x-member.bottom-nav />

    @stack('scripts')
</body>
</html>
