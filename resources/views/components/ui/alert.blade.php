@props(['type' => 'info', 'message'])

@php
    $classes = match ($type) {
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'error' => 'border-red-200 bg-red-50 text-red-800',
        default => 'border-blue-200 bg-blue-50 text-blue-800',
    };
@endphp

<div {{ $attributes->merge(['class' => "mx-auto mb-4 max-w-6xl rounded-lg border px-4 py-3 text-sm {$classes}"]) }}>
    {{ $message }}
</div>
