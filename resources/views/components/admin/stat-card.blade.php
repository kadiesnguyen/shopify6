@props(['label', 'value', 'icon' => 'users', 'iconBg' => 'bg-slate-100', 'iconColor' => 'text-slate-600'])

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 ring-1 ring-gray-200 sm:p-6']) }}>
    <div class="flex items-center gap-3">
        <div @class(['rounded-lg p-3', $iconBg])>
            <x-admin.dashboard-icon :name="$icon" @class(['size-6', $iconColor]) />
        </div>
        <div>
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="text-xl font-semibold tabular-nums text-gray-900">{{ $value }}</p>
        </div>
    </div>
</div>
