@php
    $locales = config('landing.locales', [
        'vi' => ['label' => 'Tiếng Việt', 'flag' => 'flags/vn.png'],
        'en' => ['label' => 'English', 'flag' => 'flags/us.png'],
    ]);
    $currentLocale = app()->getLocale();
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-header-bg">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <a href="{{ route('landing.home') }}" class="text-2xl font-bold text-brand">
            {{ config('landing.brand_name', 'Shopify') }}
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-slate-700 md:flex">
            <a href="{{ route('landing.home') }}" @class(['text-brand' => request()->routeIs('landing.home')])>{{ __('landing.nav.home') }}</a>
            <a href="{{ route('landing.news.index') }}" @class(['text-brand' => request()->routeIs('landing.news.*')])>{{ __('landing.nav.news') }}</a>
            <a href="{{ route('landing.about') }}" @class(['text-brand' => request()->routeIs('landing.about')])>{{ __('landing.nav.about') }}</a>
            <a href="{{ route('landing.contact') }}" @class(['text-brand' => request()->routeIs('landing.contact')])>{{ __('landing.nav.contact') }}</a>
        </nav>

        <div class="flex items-center gap-2">
            <div class="relative hidden sm:block" x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-brand/40"
                >
                    @if (! empty($locales[$currentLocale]['flag']))
                        <img src="{{ asset('images/landing/'.$locales[$currentLocale]['flag']) }}" alt="" class="h-4 w-5 shrink-0 object-cover" width="20" height="16">
                    @endif
                    <span class="whitespace-nowrap">{{ $locales[$currentLocale]['label'] ?? strtoupper($currentLocale) }}</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    class="absolute right-0 z-50 mt-1 min-w-max rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                >
                    @foreach ($locales as $code => $locale)
                        <a
                            href="{{ route('locale.switch', $code) }}"
                            @class([
                                'flex items-center gap-2 whitespace-nowrap px-4 py-2 text-sm hover:bg-slate-50',
                                'font-semibold text-brand' => $currentLocale === $code,
                                'text-slate-700' => $currentLocale !== $code,
                            ])
                        >
                            @if (! empty($locale['flag']))
                                <img src="{{ asset('images/landing/'.$locale['flag']) }}" alt="" class="h-4 w-5 shrink-0 object-cover" width="20" height="16">
                            @endif
                            <span class="whitespace-nowrap">{{ $locale['label'] ?? $code }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('auth.login') }}" class="hidden rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark sm:inline-flex">
                {{ __('messages.login') }}
            </a>

            <button
                type="button"
                class="inline-flex rounded-lg border border-slate-200 p-2 text-slate-700 md:hidden"
                x-data
                @click="$dispatch('toggle-mobile-nav')"
                aria-label="Menu"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    <div
        x-data="{ open: false }"
        @toggle-mobile-nav.window="open = !open"
        x-show="open"
        x-cloak
        class="border-t border-slate-200 bg-white md:hidden"
    >
        <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm">
            <div class="flex flex-wrap gap-2">
                @foreach ($locales as $code => $locale)
                    <a href="{{ route('locale.switch', $code) }}" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs @if($currentLocale === $code) border-brand text-brand @else border-slate-200 @endif">
                        @if (! empty($locale['flag']))
                            <img src="{{ asset('images/landing/'.$locale['flag']) }}" alt="" class="h-3 w-4 object-cover">
                        @endif
                        {{ $locale['label'] ?? $code }}
                    </a>
                @endforeach
            </div>
            <a href="{{ route('auth.login') }}" class="rounded-lg bg-brand px-4 py-2.5 text-center font-semibold text-white">{{ __('messages.login') }}</a>
            <a href="{{ route('auth.register') }}" class="rounded-lg border border-brand px-4 py-2.5 text-center font-semibold text-brand">{{ __('messages.register') }}</a>
        </nav>
    </div>
</header>
