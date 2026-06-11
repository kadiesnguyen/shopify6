@props(['role' => null])

@php
    $roleName = is_string($role) ? $role : ($role?->name ?? null);
    $styles = match ($roleName) {
        'shop' => 'bg-blue-50 text-blue-600',
        'admin' => 'bg-amber-50 text-amber-700',
        'member' => 'bg-slate-100 text-slate-600',
        default => 'bg-slate-100 text-slate-500',
    };
@endphp

@if ($roleName)
    <span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium {$styles}"]) }}>
        {{ __('admin.roles.'.$roleName) }}
    </span>
@else
    <span class="text-slate-400">—</span>
@endif
