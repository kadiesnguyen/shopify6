@props(['name'])

@php
    $paths = match ($name) {
        'arrow-down-circle' => 'M12 22a10 10 0 100-20 10 10 0 000 20zm-1-6 4 4m0 0 4-4m-4 4V8',
        'arrow-up-circle' => 'M12 22a10 10 0 100-20 10 10 0 000 20zm0-10 4 4m0 0 4-4m-4 4V16',
        'users' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zm11 4v2a4 4 0 01-4 4h-2M16 3.13a4 4 0 010 7.75',
        'user-plus' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8m5 3h6m-3-3v6',
        'ticket' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
        'package' => 'M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3zM12 12l8-4.5M12 12v9M12 12L4 7.5',
        'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'circle-plus' => 'M12 22a10 10 0 100-20 10 10 0 000 20zm0-6v-4m0 0V8m0 4h4m-4 0H8',
        'circle-arrow-out' => 'M12 22a10 10 0 100-20 10 10 0 000 20zm-4-8 4-4m0 0 4 4m-4-4h8',
        default => 'M12 22a10 10 0 100-20 10 10 0 000 20',
    };
@endphp

<svg {{ $attributes->merge(['class' => 'size-6 shrink-0', 'fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor', 'aria-hidden' => 'true']) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $paths }}" />
</svg>
