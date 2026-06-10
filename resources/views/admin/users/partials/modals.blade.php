@php
    $listQuery = request()->only(['q', 'role', 'shop_application']);
    $closeUrl = route('admin.users.index', $listQuery);
    $userLabel = $modalUser->phone ?: ($modalUser->user_code ?: $modalUser->email);
    $defaultAddress = $modalUser->shippingAddresses->sortByDesc('is_default')->first();
    $idNumber = $modalUser->shopApplications->sortByDesc('created_at')->first()?->id_number ?? '—';
@endphp

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-2 sm:items-center sm:p-4">
    <div @class([
        'flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-xl',
        'max-w-lg' => $activeModal !== 'distributions',
        'max-w-5xl' => $activeModal === 'distributions',
    ])>
        <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
            <div>
                @if ($activeModal === 'info')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.actions.info_title') }}</h3>
                @elseif ($activeModal === 'balance')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.actions.balance_title') }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('admin.users.actions.balance_subtitle', ['user' => $userLabel]) }}</p>
                @elseif ($activeModal === 'deposit')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.actions.deposit_title') }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('admin.users.actions.deposit_subtitle', ['user' => $userLabel]) }}</p>
                @elseif ($activeModal === 'password')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.actions.password_title') }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('admin.users.actions.password_subtitle', ['user' => $userLabel]) }}</p>
                @elseif ($activeModal === 'payment_password')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.actions.payment_password_title') }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('admin.users.actions.payment_password_subtitle', ['user' => $userLabel]) }}</p>
                @elseif ($activeModal === 'distributions')
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.users.distributions.title') }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('admin.users.distributions.subtitle', ['user' => $userLabel]) }}</p>
                @endif
            </div>
            <a href="{{ $closeUrl }}" class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="{{ __('admin.users.distributions.close') }}">×</a>
        </div>

        <div class="overflow-y-auto px-5 py-4">
            @if ($activeModal === 'info')
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    @foreach ([
                        ['Email', $modalUser->email ?: '—'],
                        [__('admin.columns.user_code'), $modalUser->user_code ?: '—'],
                        [__('admin.users.actions.real_name'), $modalUser->name ?: '—'],
                        [__('admin.columns.role'), ucfirst($modalUser->roles->first()?->name ?? '—')],
                        [__('admin.users.actions.shop_name'), $modalUser->shop?->name ?? '—'],
                        [__('admin.users.actions.shop_followers'), '0'],
                        [__('admin.users.actions.credit_score'), '0'],
                        [__('admin.shop_applications.phone'), $modalUser->phone ?: '—'],
                        [__('admin.users.actions.id_number'), $idNumber],
                        [__('admin.users.actions.address'), $defaultAddress?->address_line ?? '—'],
                        [__('admin.users.actions.country'), $defaultAddress?->country ?? '—'],
                        [__('admin.columns.created_at'), $modalUser->created_at?->format('Y-m-d H:i:s') ?? '—'],
                        [__('admin.columns.balance_pending'), '$'.number_format($modalUser->wallet?->balance_pending ?? 0, 2)],
                        [__('admin.columns.balance'), '$'.number_format($modalUser->wallet?->balance ?? 0, 2)],
                        [__('admin.columns.balance_frozen'), '$'.number_format($modalUser->wallet?->balance_frozen ?? 0, 2)],
                        [__('admin.users.actions.login_password'), $modalUser->phone ?: __('admin.users.actions.password_set')],
                        [__('admin.users.actions.fund_password'), $modalUser->hasPaymentPassword() ? __('admin.users.actions.password_set') : __('admin.users.actions.password_not_set')],
                    ] as [$label, $value])
                        <div>
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @elseif ($activeModal === 'balance')
                <form method="POST" action="{{ route('admin.users.balance.update', $modalUser) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    @foreach (['balance_pending' => __('admin.columns.balance_pending'), 'balance' => __('admin.users.actions.balance_available'), 'balance_frozen' => __('admin.columns.balance_frozen')] as $field => $label)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $modalUser->wallet?->$field ?? 0) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    @endforeach
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.users.actions.update_balance') }}</button>
                    </div>
                </form>
            @elseif ($activeModal === 'deposit')
                <form method="POST" action="{{ route('admin.users.deposit', $modalUser) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.note') }}</label>
                        <input type="text" name="note" value="{{ old('note') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.users.actions.deposit') }}</button>
                    </div>
                </form>
            @elseif ($activeModal === 'password')
                <form method="POST" action="{{ route('admin.users.password.update', $modalUser) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.new_password') }}</label>
                        <input type="password" name="password" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.confirm_password') }}</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
                    </div>
                </form>
            @elseif ($activeModal === 'payment_password')
                <form method="POST" action="{{ route('admin.users.payment-password.update', $modalUser) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.new_payment_password') }}</label>
                        <input type="password" name="payment_password" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.confirm_payment_password') }}</label>
                        <input type="password" name="payment_password_confirmation" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
                    </div>
                </form>
            @elseif ($activeModal === 'distributions')
                @include('admin.users.partials.distributions-modal-body')
            @endif
        </div>

        @if ($activeModal === 'info')
            <div class="border-t border-slate-100 px-5 py-3 text-right">
                <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('admin.users.distributions.close') }}</a>
            </div>
        @endif
    </div>
</div>
