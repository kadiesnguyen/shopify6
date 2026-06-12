<div {{ $attributes->merge(['class' => 'responsive-table-wrap w-full min-w-0 max-w-full']) }}>
    <div class="responsive-table-scroll w-full min-w-0 max-w-full">
        {{ $slot }}
    </div>
</div>
