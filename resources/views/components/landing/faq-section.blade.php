@props(['faqs' => collect()])

@if ($faqs->isNotEmpty())
    <section class="mx-auto max-w-6xl px-4 py-14 md:py-20">
        <h2 class="text-2xl font-bold text-brand-dark md:text-3xl">{{ __('landing.faq.title') }}</h2>

        <div class="mt-8 space-y-3" x-data="{ open: null }">
            @foreach ($faqs as $index => $faq)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium text-slate-900"
                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                    >
                        <span>{{ $faq->translate('question') }}</span>
                        <svg class="h-5 w-5 shrink-0 transition" :class="open === {{ $index }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open === {{ $index }}" x-cloak x-transition class="border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-slate-600">
                        {{ $faq->translate('answer') }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
