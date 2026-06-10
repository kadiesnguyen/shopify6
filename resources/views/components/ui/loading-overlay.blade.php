@props(['target' => 'form'])

<div
    x-data="{ loading: false }"
    x-on:submit="{{ $target === 'form' ? 'loading = true' : '' }}"
    {{ $attributes }}
>
    {{ $slot }}

    <div
        x-show="loading"
        x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center bg-white/70 backdrop-blur-[1px]"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="w-full max-w-xs px-6">
            <x-ui.skeleton-product-card />
            <p class="mt-3 text-center text-sm text-slate-500">{{ __('ui.loading') }}</p>
        </div>
    </div>
</div>
