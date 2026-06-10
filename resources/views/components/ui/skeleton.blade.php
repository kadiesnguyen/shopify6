@props(['lines' => 3])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }}>
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-4 rounded bg-slate-200"></div>
    @endfor
</div>
