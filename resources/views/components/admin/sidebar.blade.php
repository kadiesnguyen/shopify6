@php
    use App\Services\Admin\AdminAlertCountsService;

    $alertCounts = app(AdminAlertCountsService::class)->counts();

    $items = [
        ['route' => 'admin.dashboard', 'label' => __('admin.menu.overview'), 'icon' => 'dashboard', 'patterns' => ['admin.dashboard']],
        ['route' => 'admin.users.index', 'label' => __('admin.menu.users'), 'icon' => 'users', 'patterns' => ['admin.users.*']],
        ['route' => 'admin.orders.index', 'label' => __('admin.menu.orders'), 'icon' => 'orders', 'patterns' => ['admin.orders.*']],
        ['route' => 'admin.invite-codes.index', 'label' => __('admin.menu.invite_codes'), 'icon' => 'invite', 'patterns' => ['admin.invite-codes.*']],
        ['route' => 'admin.products.index', 'label' => __('admin.menu.products'), 'icon' => 'products', 'patterns' => ['admin.products.*', 'admin.categories.*']],
        ['route' => 'admin.recharge-methods.index', 'label' => __('admin.menu.recharge_methods'), 'icon' => 'recharge', 'patterns' => ['admin.recharge-methods.*']],
        ['route' => 'admin.withdrawal-methods.index', 'label' => __('admin.menu.withdrawal_methods'), 'icon' => 'withdraw', 'patterns' => ['admin.withdrawal-methods.*']],
        [
            'route' => 'admin.recharge-requests.index',
            'label' => __('admin.menu.recharge_requests'),
            'icon' => 'request-in',
            'patterns' => ['admin.recharge-requests.*'],
            'badge' => 'recharge_pending',
        ],
        [
            'route' => 'admin.withdrawal-requests.index',
            'label' => __('admin.menu.withdrawal_requests'),
            'icon' => 'request-out',
            'patterns' => ['admin.withdrawal-requests.*'],
            'badge' => 'withdrawal_pending',
        ],
        ['route' => 'admin.shop-applications.index', 'label' => __('admin.menu.shop_applications'), 'icon' => 'shop', 'patterns' => ['admin.shop-applications.*']],
        [
            'route' => 'admin.chat.index',
            'label' => __('admin.menu.chat'),
            'icon' => 'chat',
            'patterns' => ['admin.chat.*'],
            'badge' => 'chat_unread',
        ],
        ['route' => 'admin.complaints.index', 'label' => __('admin.menu.complaints'), 'icon' => 'request-in', 'patterns' => ['admin.complaints.*']],
        ['route' => 'admin.reviews.index', 'label' => __('admin.menu.reviews'), 'icon' => 'products', 'patterns' => ['admin.reviews.*']],
        ['route' => 'admin.settings.edit', 'label' => __('admin.menu.settings'), 'icon' => 'settings', 'patterns' => ['admin.settings.*']],
    ];
@endphp

<aside
    class="flex h-full min-h-screen w-72 max-w-[85vw] shrink-0 flex-col bg-admin-sidebar text-slate-200 md:w-64 md:max-w-none"
    x-data="adminAlerts({
        pollUrl: @js(route('admin.alerts.counts')),
        initial: @js($alertCounts),
    })"
>
    <div class="flex items-center justify-between border-b border-slate-700 px-4 py-4">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-admin-accent">
                <x-admin.sidebar-icon name="dashboard" class="size-5" width="20" height="20" />
            </span>
            <span class="text-lg font-semibold text-admin-accent">Admin</span>
        </div>
        <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white md:hidden"
            @click="$dispatch('admin-sidebar-close')"
            aria-label="{{ __('admin.actions.cancel') }}"
        >
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-0.5 overflow-y-auto p-3 text-sm">
        @foreach ($items as $item)
            @php
                $active = collect($item['patterns'])->contains(fn (string $p) => request()->routeIs($p));
                $badgeKey = $item['badge'] ?? null;
            @endphp
            <a
                href="{{ route($item['route']) }}"
                @click="$dispatch('admin-sidebar-close')"
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
                    'bg-slate-800 text-white shadow-sm' => $active,
                    'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $active,
                ])
            >
                <x-admin.sidebar-icon
                    :name="$item['icon']"
                    @class([
                        'text-admin-accent' => $active,
                        'text-slate-400' => ! $active,
                    ])
                />
                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                @if ($badgeKey)
                    <span
                        x-cloak
                        x-show="counts.{{ $badgeKey }} > 0"
                        x-text="formatBadge(counts.{{ $badgeKey }})"
                        class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold leading-none text-white"
                    ></span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-700 p-4 text-xs">
        <p class="mb-2 truncate">{{ auth()->user()->email }}</p>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-admin-accent hover:underline">{{ __('messages.logout') }}</button>
        </form>
    </div>
</aside>
