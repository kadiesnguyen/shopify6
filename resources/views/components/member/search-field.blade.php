@props([
    'name',
    'placeholder' => '',
    'value' => '',
    'icon' => 'search',
])

<label {{ $attributes->class([
    'portal-search-field relative flex w-full items-center gap-2.5 rounded-xl bg-white px-3.5 shadow-sm ring-1 ring-gray-200/80 transition-[box-shadow] focus-within:shadow-md focus-within:ring-2 focus-within:ring-emerald-500/40',
]) }}>
    <x-member.icon :name="$icon" class="size-[18px] shrink-0 text-gray-400" />
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
    >
</label>
