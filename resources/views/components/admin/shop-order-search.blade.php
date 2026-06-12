@props(['placeholder'])

<form method="GET" class="mb-4">
    @foreach (request()->except(['q', 'shop_id', 'page']) as $key => $val)
        @if (is_string($val))
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endif
    @endforeach

    <label class="portal-search-field relative block w-full">
        <input type="hidden" name="shop_id" value="{{ request('shop_id') }}" data-suggest-hidden>
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            spellcheck="false"
            data-member-suggest="1"
            data-suggest-url="{{ route('admin.shops.search-suggestions') }}"
            data-suggest-target="shop"
            data-suggest-context="admin"
            data-suggest-min="1"
            data-suggest-no-results="{{ __('member.search.no_suggestions') }}"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand"
        >
        <div
            data-suggest-list
            class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
        ></div>
    </label>
</form>
