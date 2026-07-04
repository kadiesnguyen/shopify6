<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=overlays-content">
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
    @vite(['resources/css/app.css', 'resources/js/member-portal.js'])
    @stack('vite')
</head>
<body class="portal-app app-layout-root overflow-x-hidden bg-gray-100 text-base text-gray-900 antialiased">
    <div class="portal-shell portal-app-shell mx-auto min-h-[var(--app-height,100dvh)] w-full min-w-0 max-w-full overflow-x-hidden bg-white md:max-w-[420px] md:shadow-xl md:ring-1 md:ring-gray-200">
        @unless(View::hasSection('hide_portal_header'))
            <x-member.header />
        @endunless

        <main @class([
            'min-w-0 flex flex-col',
            'min-h-[calc(var(--app-height,100dvh)-50px)] pb-[calc(50px+env(safe-area-inset-bottom))]' => ! View::hasSection('portal_chat_page'),
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
                @if (session('status') && ! View::hasSection('hide_status_alert'))
                    <x-ui.alert type="success" :message="session('status')" class="mb-4 !max-w-none" />
                @endif

                @if ($errors->any())
                    <x-ui.error-state class="mb-4 text-left">
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.error-state>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>

    @stack('product_buy_bar')

    @unless(View::hasSection('hide_bottom_nav'))
        <x-member.bottom-nav />
    @endunless

    @stack('scripts')
</body>
</html>
