@props([
    'name' => 'sort',
    'value' => null,
    'options' => [],
    'autoSubmit' => true,
])

@php
    $selected = $value ?? request($name, array_key_first($options));
@endphp

<div class="portal-sort-select shrink-0">
    <select
        name="{{ $name }}"
        @if ($autoSubmit) onchange="this.form.submit()" @endif
        class="h-full min-h-[42px] appearance-none rounded-xl bg-white py-2.5 pl-3.5 pr-9 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200/80 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
