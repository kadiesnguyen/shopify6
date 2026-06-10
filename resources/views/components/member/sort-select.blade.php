@props([
    'name' => 'sort',
    'value' => null,
    'options' => [],
    'autoSubmit' => true,
])

@php
    $selected = $value ?? request($name, array_key_first($options));
@endphp

<div class="portal-sort-select relative shrink-0">
    <select
        name="{{ $name }}"
        @if ($autoSubmit) onchange="this.form.submit()" @endif
        class="h-full min-h-[42px] appearance-none rounded-xl bg-white py-2.5 pl-3.5 pr-9 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200/80 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-2.5 text-gray-400" aria-hidden="true">
        <x-member.icon name="chevron-down" class="size-4" />
    </span>
</div>
