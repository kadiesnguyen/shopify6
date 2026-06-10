@props([
    'searchName' => 'q',
    'searchValue' => '',
    'searchPlaceholder' => '',
    'sortName' => 'sort',
    'sortValue' => null,
    'sortOptions' => [],
    'showSort' => true,
])

<form method="GET" {{ $attributes->merge(['class' => 'flex items-stretch gap-2.5']) }}>
    {{ $hidden ?? '' }}

    <x-member.search-field
        :name="$searchName"
        :value="$searchValue"
        :placeholder="$searchPlaceholder"
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
