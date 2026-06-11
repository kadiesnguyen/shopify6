@props(['active' => 'buyer'])

<div class="px-4 pb-2">
    <div class="flex items-center gap-2">
        @if (auth()->user()->isShop())
            <a
                href="{{ route('member.seller.orders.index') }}"
                @class([
                    'inline-flex flex-1 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold no-underline transition',
                    'border-emerald-600 bg-emerald-600 text-white' => $active === 'seller',
                    'border-gray-200 bg-white text-gray-700' => $active !== 'seller',
                ])
            >
                {{ __('member.orders.customer_orders') }}
            </a>
        @endif

        <a
            href="{{ route('member.orders.index') }}"
            @class([
                'inline-flex flex-1 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold no-underline transition',
                'border-emerald-600 bg-emerald-600 text-white' => $active === 'buyer',
                'border-gray-200 bg-white text-gray-700' => $active !== 'buyer',
            ])
        >
            {{ __('member.orders.my_orders') }}
        </a>

        <a
            href="{{ route('member.orders.index', auth()->user()->isShop() ? ['scope' => 'all'] : []) }}"
            @class([
                'inline-flex flex-1 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold no-underline transition',
                'border-emerald-600 bg-emerald-600 text-white' => $active === 'all',
                'border-gray-200 bg-white text-gray-700' => $active !== 'all',
            ])
        >
            {{ __('member.orders.all') }}
        </a>
    </div>
</div>
