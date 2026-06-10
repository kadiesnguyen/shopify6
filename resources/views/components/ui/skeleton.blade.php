@props(['lines' => 3, 'variant' => 'text'])

@if ($variant === 'product-card')
    <x-ui.skeleton-product-card {{ $attributes }} />
@elseif ($variant === 'list-item')
    <x-ui.skeleton-list-item {{ $attributes }} />
@else
    <div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }}>
        @for ($i = 0; $i < $lines; $i++)
            <div @class([
                'h-4 rounded bg-slate-200',
                'w-full' => $i % 3 !== 2,
                'w-2/3' => $i % 3 === 2,
            ])></div>
        @endfor
    </div>
@endif
