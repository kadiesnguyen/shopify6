@php
    $items = __('landing.opportunities.items');
    $images = config('landing.opportunity_cards', []);

    $cards = collect($items)->map(function (array $item, int $index) use ($images) {
        $image = $images[$index] ?? 'opportunities/case1.jpg';

        return [
            'image' => asset('images/landing/'.$image),
            'tag' => $item['tag'] ?? '',
            'title' => $item['title'],
            'description' => $item['description'],
        ];
    });

    $trackCards = $cards->concat($cards);
@endphp

<section class="overflow-hidden bg-white py-14 md:py-20">
    <div class="mx-auto max-w-6xl px-4">
        <div class="flex flex-col items-center justify-between gap-4 text-center md:flex-row md:text-left">
            <h2 class="text-2xl font-bold text-brand md:text-4xl">{{ __('landing.opportunities.title') }}</h2>
            <a href="{{ route('auth.login') }}" class="inline-flex rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                {{ __('messages.login') }}
            </a>
        </div>

        <div class="relative mt-10 overflow-hidden">
            <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-16 bg-gradient-to-r from-white to-transparent"></div>
            <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-16 bg-gradient-to-l from-white to-transparent"></div>

            <div class="landing-opportunity-track flex w-max gap-0 py-2">
                @foreach ($trackCards as $index => $card)
                    @php
                        $isDuplicate = $index >= $cards->count();
                    @endphp
                    <article
                        @class([
                            'mx-[15px] w-[280px] shrink-0 overflow-hidden rounded-xl bg-white shadow-lg transition duration-300 hover:-translate-y-2 hover:shadow-xl sm:w-[320px] md:w-[350px]',
                        ])
                        @if($isDuplicate) aria-hidden="true" @endif
                    >
                        <img
                            src="{{ $card['image'] }}"
                            alt="{{ $isDuplicate ? '' : $card['title'] }}"
                            class="h-[200px] w-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        >
                        <div class="p-5">
                            <span class="inline-block rounded-full bg-brand px-2.5 py-1 text-xs font-medium text-white">{{ $card['tag'] }}</span>
                            <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $card['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $card['description'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
