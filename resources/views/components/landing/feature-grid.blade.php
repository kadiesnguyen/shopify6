@php
    $features = __('landing.features.items');
    $images = config('landing.feature_images', []);
@endphp

<section class="mx-auto max-w-6xl px-4 py-14 md:py-20">
    <div class="mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold text-brand md:text-4xl">{{ __('landing.features.title') }}</h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600 md:text-lg">{{ __('landing.features.intro') }}</p>
    </div>

    <div class="mt-12 flex flex-wrap justify-between gap-y-8">
        @foreach ($features as $index => $feature)
            @php
                $imagePath = $images[$index] ?? null;
            @endphp
            <article class="group w-full rounded-lg bg-white p-6 shadow-md transition duration-300 hover:-translate-y-2 hover:shadow-lg sm:w-[48%] lg:w-[30%]">
                <div class="mb-5 flex h-28 items-center justify-center overflow-hidden rounded-md bg-slate-50">
                    @if ($imagePath)
                        <img
                            src="{{ asset('images/landing/'.$imagePath) }}"
                            alt="{{ $feature['title'] }}"
                            class="h-full w-full object-contain"
                            loading="lazy"
                            decoding="async"
                        >
                    @endif
                </div>
                <h3 class="text-xl font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $feature['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>
