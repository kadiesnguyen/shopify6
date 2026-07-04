@props(['message' => null])

@if ($message)
    <div
        x-data="{
            open: true,
            init() {
                setTimeout(() => { this.open = false; }, 2400);
            },
        }"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="pointer-events-none fixed inset-x-0 top-1/2 z-[250] flex -translate-y-1/2 justify-center px-6 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
        role="status"
        aria-live="polite"
    >
        <p {{ $attributes->merge(['class' => 'rounded-lg bg-[#4a4a4a]/95 px-5 py-3 text-center text-sm text-white shadow-lg']) }}>
            {{ $message }}
        </p>
    </div>
@endif
