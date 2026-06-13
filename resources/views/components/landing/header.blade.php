@php
    use App\Support\AppLocale;
    use App\Support\SiteSettings;

    $locales = AppLocale::configured();
    $currentLocale = AppLocale::display();
    $currentMeta = AppLocale::currentMeta() ?? [];
    $portalLogo = SiteSettings::logoUrl();
    $brandName = SiteSettings::websiteTitle();
@endphp

<header class="sticky top-0 z-40 bg-brand text-white shadow-sm">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="{{ route('landing.home') }}" class="inline-flex rounded-md bg-white px-2.5 py-1.5">
            <img
                src="{{ $portalLogo }}"
                alt="{{ $brandName }}"
                class="h-6 w-auto max-w-[7rem] object-contain object-left sm:h-7"
                width="200"
                height="40"
            >
        </a>

        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-white hover:bg-white/10"
                >
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m12.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5a17.92 17.92 0 01-8.716-2.247m0 0A8.966 8.966 0 013 12c0-1.105.203-2.162.572-3.132"/>
                    </svg>
                    @if (! empty($currentMeta['flag']))
                        <img src="{{ asset('images/landing/'.$currentMeta['flag']) }}" alt="" class="h-4 w-5 shrink-0 object-cover" width="20" height="16">
                    @endif
                    <span class="max-w-[7rem] truncate sm:max-w-none sm:whitespace-nowrap">{{ $currentMeta['label'] ?? strtoupper($currentLocale) }}</span>
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    class="absolute right-0 z-50 mt-1 max-h-64 min-w-max overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 text-gray-700 shadow-lg"
                >
                    @foreach ($locales as $code => $locale)
                        <a
                            href="{{ route('locale.switch', $code) }}"
                            @class([
                                'flex items-center gap-2 whitespace-nowrap px-4 py-2 text-sm hover:bg-gray-50',
                                'font-semibold text-brand' => $currentLocale === $code,
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

            <a href="{{ route('auth.login') }}" class="hidden rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand hover:bg-white/90 sm:inline-flex">
                {{ __('messages.login') }}
            </a>

            <button
                type="button"
                class="inline-flex rounded-lg p-2 text-white hover:bg-white/10 md:hidden"
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
        <div class="landing-mobile-nav-panel__body mx-auto flex max-w-6xl flex-col items-center px-4 pt-4">
            <div class="flex w-full gap-2">
                <a href="{{ route('auth.register') }}" class="flex-1 rounded-lg border border-brand px-4 py-2.5 text-center text-sm font-semibold text-brand">
                    {{ __('messages.register') }}
                </a>
                <a href="{{ route('auth.login') }}" class="flex-1 rounded-lg bg-brand px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-dark">
                    {{ __('messages.login') }}
                </a>
            </div>
        </div>
    </div>
</header>
