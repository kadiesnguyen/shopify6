@props(['title' => null, 'description' => null, 'icon' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center sm:px-6 sm:py-12']) }}>
    @if ($icon)
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10.5 11.25h3M5.25 7.5h13.5M9 7.5V4.875c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V7.5"/>
            </svg>
        </div>
    @endif
    <p class="text-base font-medium text-slate-700">{{ $title ?? __('ui.empty.default') }}</p>
    @if ($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
