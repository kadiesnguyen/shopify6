@props(['banners' => collect(), 'rounded' => false])

@php
    $slides = $banners->isNotEmpty()
        ? $banners->map(fn ($banner) => [
            'image' => str_starts_with($banner->image, 'images/')
                ? asset($banner->image)
                : asset('storage/'.$banner->image),
            'alt' => $banner->translate('title') ?: 'Banner',
        ])
        : collect(config('portal.banners', []))->map(fn (string $path) => [
            'image' => asset($path),
            'alt' => 'Banner',
        ]);
@endphp

@if ($slides->isNotEmpty())
    <section
        {{ $attributes->class(['relative overflow-hidden bg-gray-100', 'rounded-[11px]' => $rounded]) }}
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
        {{-- Reference: full-bleed 2:1 banner, dash indicators, no arrows --}}
        <div class="relative aspect-[2/1] w-full overflow-hidden">
            @foreach ($slides as $index => $slide)
                <img
                    src="{{ $slide['image'] }}"
                    alt="{{ $slide['alt'] }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                    @if ($index === 0) fetchpriority="high" @endif
                    class="absolute inset-0 h-full w-full max-w-none object-cover object-center transition-opacity duration-500"
                    x-show="current === {{ $index }}"
                    @if ($index) x-cloak @endif
                >
            @endforeach

            @if ($slides->count() > 1)
                <div class="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 gap-1.5">
                    @foreach ($slides as $index => $slide)
                        <button
                            type="button"
                            @click="goTo({{ $index }})"
                            :class="current === {{ $index }} ? 'bg-gray-100' : 'bg-gray-400/70'"
                            class="h-1 w-4 rounded-sm transition-colors"
                            aria-label="Slide {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
