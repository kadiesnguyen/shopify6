@props(['type' => null, 'compact' => false])

@php
    use App\Models\Shop;

    $styles = match ($type) {
        Shop::TYPE_BUSINESS => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200',
        Shop::TYPE_PERSONAL => 'bg-violet-100 text-violet-800 ring-1 ring-violet-200',
        default => 'bg-slate-100 text-slate-600',
    };

    $label = match ($type) {
        Shop::TYPE_BUSINESS => $compact
            ? __('admin.shop_applications.type_business')
            : __('admin.shop_types.business'),
        Shop::TYPE_PERSONAL => $compact
            ? __('admin.shop_applications.type_personal')
            : __('admin.shop_types.personal'),
        default => null,
    };
@endphp

@if ($label)
    <span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold {$styles}"]) }}>
        <span class="truncate">{{ $label }}</span>
    </span>
@endif
