@props(['href', 'icon', 'label'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition hover:bg-gray-50']) }}>
    <span class="flex items-center gap-3">
        <x-member.icon :name="$icon" class="size-5 text-emerald-600" />
        <span class="font-semibold text-gray-900">{{ $label }}</span>
    </span>
    <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
</a>
