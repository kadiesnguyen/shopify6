@props([
    'value' => '',
    'placeholder' => '',
])

<label {{ $attributes->merge(['class' => 'portal-search-field relative block w-full lg:min-w-[220px] lg:flex-1']) }}>
    <input type="hidden" name="user_id" value="{{ request('user_id') }}" data-suggest-hidden>
    <input
        type="search"
        name="q"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        spellcheck="false"
        data-member-suggest="1"
        data-suggest-url="{{ route('admin.users.search-suggestions') }}"
        data-suggest-target="user"
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
