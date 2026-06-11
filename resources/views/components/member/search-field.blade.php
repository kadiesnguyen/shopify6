@props([
    'name',
    'placeholder' => '',
    'value' => '',
    'icon' => 'search',
    'autocomplete' => false,
    'suggestTarget' => 'product',
    'suggestContext' => 'portal',
    'hiddenFieldName' => null,
    'hiddenFieldValue' => '',
])

<label {{ $attributes->class([
    'portal-search-field relative flex w-full items-center gap-2.5 rounded-xl bg-white px-3.5 shadow-sm ring-1 ring-gray-200/80 transition-[box-shadow] focus-within:shadow-md focus-within:ring-2 focus-within:ring-emerald-500/40',
]) }}>
    @if ($hiddenFieldName)
        <input type="hidden" name="{{ $hiddenFieldName }}" value="{{ $hiddenFieldValue }}" data-suggest-hidden>
    @endif
    <x-member.icon :name="$icon" class="size-[18px] shrink-0 text-gray-400" />
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        spellcheck="false"
        data-member-suggest="{{ $autocomplete ? '1' : '0' }}"
        data-suggest-url="{{ route('member.search.suggestions') }}"
        data-suggest-target="{{ $suggestTarget }}"
        data-suggest-context="{{ $suggestContext }}"
        data-suggest-min="1"
        data-suggest-no-results="{{ __('member.search.no_suggestions') }}"
        class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
    >
    @if ($autocomplete)
        <div
            data-suggest-list
            class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
        ></div>
    @endif
</label>
