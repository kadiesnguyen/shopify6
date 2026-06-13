@php
    use App\Support\AppLocale;
    use App\Support\Member\BellNotificationCache;
    use App\Support\SiteSettings;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $unreadCount = BellNotificationCache::unreadCount($user->id);
    $locales = AppLocale::configured();
    $currentLocale = AppLocale::display();
    $currentMeta = AppLocale::currentMeta() ?? [];
    $logo = SiteSettings::logoUrl();
    $brandName = SiteSettings::websiteTitle();
    $displayName = $user->email ?: ($user->username ?? $user->name);
    $displayNameShort = Str::length($displayName) > 10
        ? Str::substr($displayName, 0, 10).'...'
        : $displayName;
@endphp

<header class="portal-header fixed inset-x-0 top-0 z-40 flex shrink-0 items-center justify-between bg-emerald-600 px-4 py-3 text-white md:left-1/2 md:right-auto md:w-full md:max-w-[420px] md:-translate-x-1/2">
    <div class="flex min-w-0 flex-1 items-center gap-2">
        <a href="{{ route('member.home') }}" class="flex min-w-0 flex-1 items-center gap-2">
            <img src="{{ $logo }}" alt="{{ $brandName }}" class="h-8 w-auto shrink-0 rounded object-contain" width="79" height="32" decoding="async" fetchpriority="high">
        </a>
    </div>

    <div class="flex shrink-0 items-center gap-2">
        <div class="relative" x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-white transition-colors hover:bg-white/10"
            >
                <x-member.icon name="globe" class="size-4 shrink-0" />
                @if (! empty($currentMeta['flag']))
                    <img src="{{ asset('images/landing/'.$currentMeta['flag']) }}" alt="" class="h-4 w-5 shrink-0 object-cover" width="20" height="16">
                @endif
                <span class="whitespace-nowrap">{{ $currentMeta['label'] ?? strtoupper($currentLocale) }}</span>
                <x-member.icon name="chevron-down" class="size-5 shrink-0" />
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
                            'font-semibold text-emerald-600' => $currentLocale === $code,
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

        <a href="{{ route('member.notifications.index') }}" class="relative rounded-md p-1.5 text-white hover:bg-white/10" aria-label="{{ __('member.notifications.title') }}">
            <x-member.icon name="bell" class="size-5 shrink-0" />
            @if ($unreadCount > 0)
                <span class="absolute -right-0.5 -top-0.5 inline-flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold">{{ min($unreadCount, 9) }}{{ $unreadCount > 9 ? '+' : '' }}</span>
            @endif
        </a>

        <a href="{{ route('member.my.index') }}" class="inline-flex max-w-[9rem] items-center gap-1 truncate rounded-full bg-white/15 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-white/25 sm:max-w-[11rem]">
            <span class="truncate">{{ $displayNameShort }}</span>
        </a>
    </div>
</header>
