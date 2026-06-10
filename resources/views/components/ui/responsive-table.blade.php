<div {{ $attributes->merge(['class' => 'responsive-table-wrap w-full min-w-0 max-w-full']) }}>
    <div class="responsive-table-scroll overflow-x-auto overscroll-x-contain">
        {{ $slot }}
    </div>
</div>
