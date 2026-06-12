@props(['application', 'compact' => false])

@php
    use App\Models\ShopApplication;

    $kindStyles = $application->isUpgrade()
        ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
        : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';

    $kindLabel = $application->isUpgrade()
        ? __('admin.shop_applications.kind_upgrade')
        : __('admin.shop_applications.kind_registration');
@endphp

<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-col items-start gap-1']) }}>
    <span class="inline-flex max-w-full items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold {{ $kindStyles }}">
        <span class="truncate">{{ $kindLabel }}</span>
    </span>
    <x-admin.shop-type-badge :type="$application->seller_type" :compact="$compact" />
</div>
