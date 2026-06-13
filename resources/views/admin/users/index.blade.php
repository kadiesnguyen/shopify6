@extends('layouts.admin')

@section('title', __('admin.users.title'))

@section('content')
    @php($listQuery = request()->only(['q', 'user_id', 'role', 'shop_application']))

    <x-admin.page-header :title="__('admin.users.title')" :action-url="route('admin.users.create')" />

    <p class="mb-4 text-sm text-slate-600">{{ __('admin.users.subtitle') }}</p>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center">
        @foreach (['show_info', 'show_edit', 'show_balance', 'show_deposit', 'show_password', 'show_payment_password', 'show_distributions'] as $modalField)
            @if (request($modalField))
                <input type="hidden" name="{{ $modalField }}" value="{{ request($modalField) }}">
            @endif
        @endforeach
        @foreach (['dist_q', 'dist_commission_type', 'dist_price_from', 'dist_price_to', 'dist_sort'] as $distField)
            @if (request($distField))
                <input type="hidden" name="{{ $distField }}" value="{{ request($distField) }}">
            @endif
        @endforeach
        <x-admin.user-search-field
            :value="request('q')"
            :placeholder="__('admin.users.search')"
        />
        <select name="role" class="w-full rounded-lg border-slate-300 text-sm sm:w-auto" onchange="this.form.submit()">
            <option value="">{{ __('admin.users.all_roles') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ __('admin.roles.'.$role) }}</option>
            @endforeach
        </select>
        <select name="shop_application" class="w-full rounded-lg border-slate-300 text-sm sm:w-auto" onchange="this.form.submit()">
            <option value="">{{ __('admin.users.shop_application_filter') }}</option>
            <option value="pending" @selected(request('shop_application') === 'pending')>{{ __('admin.users.shop_application_pending') }}</option>
            <option value="approved" @selected(request('shop_application') === 'approved')>{{ __('admin.users.shop_application_approved') }}</option>
            <option value="none" @selected(request('shop_application') === 'none')>{{ __('admin.users.shop_application_none') }}</option>
        </select>
        <a href="{{ route('admin.shop-applications.index', ['status' => 'pending']) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand px-3 py-2 text-sm font-medium text-brand hover:bg-brand/5 sm:w-auto">
            {{ __('admin.users.seller_applications') }}
        </a>
    </form>

    <div class="space-y-3 md:hidden">
        @forelse ($users as $user)
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ $user->name }}</p>
                        <x-admin.shop-application-pending-badge :user="$user" />
                        <p class="break-words text-sm text-slate-600">{{ $user->loginIdentifier() ?? '—' }}</p>
                    </div>
                    <x-admin.user-actions-menu :user="$user" :list-query="$listQuery" />
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div><dt class="text-slate-500">{{ __('admin.columns.role') }}</dt><dd><x-admin.role-badge :role="$user->adminFormRole()" :shop="$user->shop" /></dd></div>
                    <div><dt class="text-slate-500">{{ __('admin.columns.shop') }}</dt><dd class="break-words font-medium">{{ $user->shop?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('admin.columns.balance') }}</dt><dd class="font-medium">${{ number_format($user->wallet?->balance ?? 0, 2) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('admin.columns.balance_pending') }}</dt><dd class="font-medium">${{ number_format($user->wallet?->balance_pending ?? 0, 2) }}</dd></div>
                </dl>
            </article>
        @empty
            <x-ui.empty-state :title="__('admin.users.empty')" class="rounded-xl bg-white" />
        @endforelse
        <div>{{ $users->links() }}</div>
    </div>

    <div class="admin-users-table hidden min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <x-ui.responsive-table>
            <table class="w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach (['email_phone', 'name', 'role', 'shop', 'balance_pending', 'balance', 'balance_frozen', 'status', 'actions'] as $col)
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.columns.'.$col) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2.5">
                                <span class="cell-wrap">{{ $user->loginIdentifier() ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="cell-truncate font-medium text-slate-900" title="{{ $user->name }}">{{ $user->name }}</span>
                                <x-admin.shop-application-pending-badge :user="$user" />
                            </td>
                            <td class="px-3 py-2.5"><x-admin.role-badge :role="$user->adminFormRole()" :shop="$user->shop" /></td>
                            <td class="px-3 py-2.5">
                                <span class="cell-wrap">{{ $user->shop?->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap">${{ number_format($user->wallet?->balance_pending ?? 0, 2) }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap">${{ number_format($user->wallet?->balance ?? 0, 2) }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap">${{ number_format($user->wallet?->balance_frozen ?? 0, 2) }}</td>
                            <td class="px-3 py-2.5">
                                @if ($user->status === \App\Models\User::STATUS_BANNED)
                                    <span class="text-xs font-medium text-red-600">{{ __('admin.users.status_banned') }}</span>
                                @else
                                    <span class="text-xs font-medium text-emerald-600">{{ __('admin.users.status_active') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <x-admin.user-actions-menu :user="$user" :list-query="$listQuery" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">{{ __('admin.users.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.responsive-table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    </div>

    @if ($modalUser && $activeModal)
        @include('admin.users.partials.modals')
    @endif
@endsection
