@props([
    'label',
    'labelColor' => 'text-gray-900',
])

<div {{ $attributes->merge(['class' => 'portal-form-field border-b border-gray-100 py-3.5']) }}>
    <p @class(['mb-2 text-base font-bold', $labelColor])>{{ $label }}</p>
    {{ $slot }}
</div>
