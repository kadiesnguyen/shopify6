@php
    use App\Services\Member\CartService;

    $cartCount = app(CartService::class)->countFor(auth()->user());
@endphp

{{-- Reference: transparent search bar overlaid on the hero banner --}}
<div class="absolute inset-x-0 top-0 z-20 flex items-center gap-2 p-[11px]">
    <form method="GET" action="{{ route('member.home') }}" class="min-w-0 flex-1">
        <label class="portal-home-search portal-search-field relative flex h-[34px] w-full items-center gap-2 rounded-[6px] bg-[#f2f2f2] px-2.5">
            <input type="hidden" name="shop_id" value="{{ request('shop_id') }}" data-suggest-hidden>
            <x-member.icon name="search" class="size-4 shrink-0 text-gray-500" />
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="{{ __('member.search.combined') }}"
                autocomplete="off"
                spellcheck="false"
                data-member-suggest="1"
                data-suggest-url="{{ route('member.search.suggestions') }}"
                data-suggest-target="combined"
                data-suggest-context="portal"
                data-suggest-min="1"
                data-suggest-no-results="{{ __('member.search.no_suggestions') }}"
                class="min-w-0 flex-1 border-0 bg-transparent text-[15px] text-gray-700 placeholder:text-[#ff6600] focus:outline-none focus:ring-0"
            >
            <div data-suggest-list class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"></div>
        </label>
    </form>
    <a href="{{ route('member.cart.index') }}" class="relative shrink-0 p-1 text-gray-800" aria-label="{{ __('member.nav.cart') }}">
        <x-member.icon name="shopping-cart" class="size-6" />
        @if ($cartCount > 0)
            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ min($cartCount, 99) }}</span>
        @endif
    </a>
    <a href="{{ route('member.chat.index') }}" class="shrink-0 p-1 text-gray-800" aria-label="{{ __('member.nav.support') }}">
        <x-member.icon name="chat-bubble" class="size-6" />
    </a>
</div>
