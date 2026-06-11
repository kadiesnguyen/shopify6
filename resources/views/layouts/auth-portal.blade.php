<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=overlays-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.login')) — {{ config('landing.brand_name', 'Shopify') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-portal-layout app-layout-root flex min-h-[var(--app-height,100dvh)] flex-col overflow-x-hidden bg-white text-base text-slate-900 antialiased" x-data>
    <x-auth.portal-header />

    <main class="flex flex-1 flex-col items-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>

    <x-member.chat-widget
        mode="guest"
        :messages-url="route('guest.chat.messages.index')"
        :send-url="route('guest.chat.messages.store')"
    />
    @yield('after_chat')
    @stack('scripts')
</body>
</html>
