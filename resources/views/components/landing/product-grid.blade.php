@props(['products' => collect()])

<section class="mx-auto max-w-6xl px-4 py-14 md:py-20">
    <div class="flex items-end justify-between gap-4">
        <h2 class="text-2xl font-bold text-brand-dark md:text-3xl">{{ __('landing.products.title') }}</h2>
        <a href="{{ route('auth.login') }}" class="text-sm font-semibold text-brand hover:text-brand-dark">{{ __('landing.products.view_all') }} →</a>
    </div>

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('landing.products.title')" class="mt-8" />
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="aspect-square bg-gradient-to-br from-slate-100 to-slate-200">
                        <div class="flex h-full items-center justify-center text-slate-400">
                            <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="line-clamp-2 font-semibold text-slate-900">{{ $product->name }}</h3>
                        <p class="mt-1 text-lg font-bold text-brand">${{ number_format($product->selling_price, 2) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('landing.products.stock') }}: {{ $product->stock }}</p>
                        <a href="{{ route('auth.login') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-brand px-3 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                            {{ __('landing.products.buy') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
