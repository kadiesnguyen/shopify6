@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 px-4 py-8 text-center sm:px-6 sm:py-10']) }} role="alert">
    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-600">
        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
    </div>
    <p class="text-base font-semibold text-red-900">{{ $title ?? __('ui.error.title') }}</p>
    @if ($description || $slot->isNotEmpty())
        <div class="mt-2 text-sm text-red-700">
            @if ($description)
                <p>{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    @endif
</div>
