@props([
    'src',
    'alt' => '',
    'wrapperClass' => '',
    'class' => '',
    'eager' => false,
    'highPriority' => false,
])

@if ($src)
    <div
        {{ $attributes->merge(['class' => 'ui-lazy-image relative overflow-hidden bg-slate-100 '.$wrapperClass]) }}
    >
        <div class="ui-lazy-image__placeholder absolute inset-0 animate-pulse bg-slate-200" aria-hidden="true"></div>
        <div class="ui-lazy-image__fallback absolute inset-0 items-center justify-center bg-slate-50 text-slate-300" aria-hidden="true">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
            </svg>
        </div>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="async"
            @if ($highPriority) fetchpriority="high" @endif
            onload="this.classList.add('!opacity-100'); this.closest('.ui-lazy-image')?.classList.add('ui-lazy-image--loaded')"
            onerror="this.closest('.ui-lazy-image')?.classList.add('ui-lazy-image--loaded', 'ui-lazy-image--failed')"
            @class([
                $class,
                'h-full w-full max-w-full object-cover opacity-0 transition-opacity duration-300',
            ])
        >
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-slate-100 text-slate-300 '.$wrapperClass]) }}>
        {{ $slot }}
    </div>
@endif
