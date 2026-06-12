@props(['role' => null, 'shop' => null])

@php
    use App\Models\Shop;

    $roleName = is_string($role) ? $role : ($role?->name ?? null);
@endphp

@if ($shop || $roleName === 'shop')
    <x-admin.shop-type-badge :type="$shop?->seller_type ?? Shop::TYPE_PERSONAL" {{ $attributes }} />
@elseif ($roleName)
    @php
        $styles = match ($roleName) {
            'admin' => 'bg-amber-50 text-amber-700',
            'member' => 'bg-slate-100 text-slate-600',
            default => 'bg-slate-100 text-slate-500',
        };
    @endphp
    <span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium {$styles}"]) }}>
        {{ __('admin.roles.'.$roleName) }}
    </span>
@else
    <span class="text-slate-400">—</span>
@endif
