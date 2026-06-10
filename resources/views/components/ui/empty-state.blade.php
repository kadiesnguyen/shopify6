@props(['title' => 'No data', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <p class="text-base font-medium text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
