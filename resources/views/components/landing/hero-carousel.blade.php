@props(['banners' => collect()])

@php
    $slides = collect(config('landing.hero_slides', []))->map(function (array $slide) {
        return [
            'image' => asset('images/landing/'.$slide['image']),
            'alt' => $slide['alt'] ?? config('app.name'),
        ];
    });

    if ($slides->isEmpty() && $banners->isNotEmpty()) {
        $slides = $banners->map(fn ($banner) => [
            'image' => $banner->image_url ?? asset('images/landing/hero/TG11.png'),
            'alt' => $banner->translate('title'),
        ]);
    }
@endphp

<section
    class="relative overflow-hidden bg-brand"
    x-data="{
        current: 0,
        total: {{ $slides->count() }},
        timer: null,
        next() { this.current = (this.current + 1) % this.total },
        goTo(index) { this.current = index },
        start() { this.timer = setInterval(() => this.next(), 5000) },
        stop() { clearInterval(this.timer) }
    }"
    x-init="start()"
    @mouseenter="stop()"
    @mouseleave="start()"
>
    <div class="relative h-[70vh] min-h-[400px] max-h-[600px] w-full">
        @foreach ($slides as $index => $slide)
            <div
                x-show="current === {{ $index }}"
                x-transition:enter="transition ease-in-out duration-500"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in-out duration-500"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0"
                @if($index > 0) x-cloak @endif
            >
                <img
                    src="{{ $slide['image'] }}"
                    alt="{{ $slide['alt'] }}"
                    class="h-full w-full object-cover object-center"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                    @if($index === 0) fetchpriority="high" @endif
                >
            </div>
        @endforeach

        <div class="absolute bottom-5 left-1/2 z-10 flex -translate-x-1/2 gap-2.5">
            @foreach ($slides as $index => $slide)
                <button
                    type="button"
                    @click="goTo({{ $index }})"
                    :class="current === {{ $index }} ? 'bg-white' : 'bg-white/50'"
                    class="h-3 w-3 rounded-full transition-colors"
                    aria-label="Slide {{ $index + 1 }}"
                ></button>
            @endforeach
        </div>
    </div>
</section>
