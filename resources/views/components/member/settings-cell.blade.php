@props(['href', 'label'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center justify-between border-b border-gray-100 bg-white px-4 py-3.5 text-sm text-gray-900 no-underline last:border-b-0']) }}>
    <span>{{ $label }}</span>
    <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
</a>
