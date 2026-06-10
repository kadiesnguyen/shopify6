@props(['href', 'icon', 'label', 'iconColor' => 'text-emerald-600', 'iconBg' => 'bg-emerald-50'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center justify-between px-4 py-3 transition hover:bg-gray-50 border-b border-gray-50 last:border-0']) }}>
    <span class="flex items-center gap-3">
        <span @class(['inline-flex size-9 items-center justify-center rounded-full', $iconBg, $iconColor])>
            <x-member.icon :name="$icon" class="size-5" />
        </span>
        <span class="text-gray-800">{{ $label }}</span>
    </span>
    <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
</a>
