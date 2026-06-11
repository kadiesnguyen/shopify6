@props([
    'searchName' => 'q',
    'searchValue' => '',
    'searchPlaceholder' => '',
    'sortName' => 'sort',
    'sortValue' => null,
    'sortOptions' => [],
    'showSort' => true,
    'searchAutocomplete' => false,
    'searchSuggestTarget' => 'product',
    'searchSuggestContext' => 'portal',
])

<form method="GET" {{ $attributes->merge(['class' => 'flex items-stretch gap-2.5']) }}>
    {{ $hidden ?? '' }}

    <x-member.search-field
        :name="$searchName"
        :value="$searchValue"
        :placeholder="$searchPlaceholder"
        :autocomplete="$searchAutocomplete"
        :suggest-target="$searchSuggestTarget"
        :suggest-context="$searchSuggestContext"
        icon="search"
        class="min-w-0 flex-1"
    />

    @if ($showSort && $sortOptions !== [])
        <x-member.sort-select
            :name="$sortName"
            :value="$sortValue"
            :options="$sortOptions"
        />
    @endif
</form>
