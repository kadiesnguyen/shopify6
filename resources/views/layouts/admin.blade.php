<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.admin_portal')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-layout-root overflow-x-hidden bg-slate-100 text-slate-900 antialiased" x-data="{ sidebarOpen: false }" @admin-sidebar-close.window="sidebarOpen = false">
    <div class="flex min-h-screen min-w-0 max-w-full overflow-x-hidden">
        <div
            class="fixed inset-0 z-30 bg-black/50 transition-opacity md:hidden"
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
        ></div>

        <div
            class="fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-200 ease-out md:static md:translate-x-0"
            :class="sidebarOpen && 'translate-x-0'"
        >
            <x-admin.sidebar />
        </div>

        <div @class([
            'flex min-w-0 flex-1 flex-col md:ml-0',
            'h-svh max-h-svh overflow-hidden' => View::hasSection('admin_chat_page'),
        ])>
            <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 md:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex shrink-0 rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 md:hidden"
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="Menu"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0 md:hidden">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>
                        <p class="truncate text-xs text-slate-500">{{ __('messages.admin_portal') }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.user-menu />
                </div>
            </header>

            <main @class([
                'flex-1 overflow-x-hidden p-4 md:p-6',
                'flex min-h-0 flex-col overflow-hidden p-2 md:p-4' => View::hasSection('admin_chat_page'),
            ])>
                @if (session('status'))
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

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
