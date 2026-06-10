@props([
    'src',
    'alt' => '',
    'wrapperClass' => '',
    'class' => '',
    'eager' => false,
])

@if ($src)
    <div
        {{ $attributes->merge(['class' => 'ui-lazy-image relative overflow-hidden bg-slate-100 '.$wrapperClass]) }}
        x-data="{ loaded: false, failed: false }"
    >
        <div
            class="absolute inset-0 animate-pulse bg-slate-200"
            x-show="!loaded && !failed"
            x-cloak
            aria-hidden="true"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center bg-slate-50 text-slate-300"
            x-show="failed"
            x-cloak
            aria-hidden="true"
        >
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
            </svg>
        </div>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="async"
            x-on:load="loaded = true"
            x-on:error="failed = true; loaded = true"
            @class([
                $class,
                'h-full w-full max-w-full object-cover opacity-0 transition-opacity duration-300',
            ])
            :class="loaded && !failed && '!opacity-100'"
        >
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-slate-100 text-slate-300 '.$wrapperClass]) }}>
        {{ $slot }}
    </div>
@endif
