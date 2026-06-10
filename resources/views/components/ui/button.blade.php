@props(['variant' => 'primary', 'type' => 'button'])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        default => 'bg-brand text-white hover:bg-brand-dark',
    };
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition {$classes}"]) }}>
    {{ $slot }}
</button>
