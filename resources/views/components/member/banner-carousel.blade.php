@props(['banners' => collect()])

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
        class="relative overflow-hidden bg-black"
        x-data="{
            current: 0,
            total: {{ $slides->count() }},
            timer: null,
            prev() { this.current = (this.current - 1 + this.total) % this.total },
            next() { this.current = (this.current + 1) % this.total },
            goTo(index) { this.current = index },
            start() { this.timer = setInterval(() => this.next(), 5000) },
            stop() { clearInterval(this.timer) }
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
    >
        <div class="relative h-[min(48dvh,440px)] min-h-[200px] w-full overflow-hidden">
            @foreach ($slides as $index => $slide)
                <img
                    src="{{ $slide['image'] }}"
                    alt="{{ $slide['alt'] }}"
                    class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-500"
                    x-show="current === {{ $index }}"
                    @if ($index) x-cloak @endif
                >
            @endforeach

            <button
                type="button"
                @click="prev()"
                class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30"
                aria-label="{{ __('member.carousel.prev') }}"
            >
                <x-member.icon name="chevron-left" class="size-5" />
            </button>
            <button
                type="button"
                @click="next()"
                class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30"
                aria-label="{{ __('member.carousel.next') }}"
            >
                <x-member.icon name="chevron-right" class="size-5" />
            </button>

            <div class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 gap-1.5">
                @foreach ($slides as $index => $slide)
                    <button
                        type="button"
                        @click="goTo({{ $index }})"
                        :class="current === {{ $index }} ? 'bg-white w-6' : 'bg-white/50 w-2'"
                        class="h-2 rounded-full transition-all"
                        aria-label="Slide {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </section>
@endif
