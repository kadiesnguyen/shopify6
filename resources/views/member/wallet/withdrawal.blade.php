@extends('layouts.member')

@section('title', __('member.wallet.withdraw_title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div
        x-data="withdrawalForm({
            methods: @js($methods),
            balance: @js($balance),
            oldMethodId: @js(old('withdrawal_method_id')),
            oldAmount: @js(old('amount')),
            oldCurrency: @js(old('currency')),
            oldNetwork: @js(old('network')),
            oldCryptoAddress: @js(old('crypto_address')),
            oldBankAccountName: @js(old('bank_account_name')),
            oldBankName: @js(old('bank_name')),
            oldBankAccountNumber: @js(old('bank_account_number')),
        })"
        class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(6rem+env(safe-area-inset-bottom))]"
    >
        <header class="sticky top-0 z-10 bg-black text-white">
            <div class="relative flex items-center justify-between px-4 py-3">
                <a href="{{ route('member.my.index') }}" class="flex items-center gap-1.5 text-white/90 no-underline">
                    <x-member.icon name="chevron-left" class="size-5" />
                    <span class="text-sm font-medium">{{ __('member.back') }}</span>
                </a>
                <span class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.wallet.withdraw_title') }}</span>
                <a href="{{ route('member.wallet.fund-records', ['type' => 'withdrawal']) }}" class="text-sm font-medium text-white/90 no-underline">{{ __('member.wallet.history') }}</a>
            </div>
        </header>

        <div class="p-4">
            @if ($errors->any())
                <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('member.wallet.withdrawal.store') }}" class="portal-wallet-form space-y-4">
                @csrf
                <input type="hidden" name="withdrawal_method_id" :value="selectedMethodId ?? ''">
                <input type="hidden" name="currency" :value="currencyValue">
                <input type="hidden" name="network" :value="networkValue">

                <section class="rounded-xl bg-white px-4 py-1 shadow-sm">
                    <button type="button" @click="methodSheetOpen = true" class="w-full border-b border-gray-100 py-3.5 text-left">
                        <p class="mb-2 text-base font-bold text-red-600">{{ __('member.wallet.withdraw_method') }}</p>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-base" :class="selectedMethod ? 'font-medium text-gray-900' : 'text-gray-400'" x-text="selectedMethod?.name ?? @js(__('member.wallet.method_placeholder'))"></p>
                            <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
                        </div>
                    </button>

                    <template x-if="selectedMethod?.type === 'crypto'">
                        <button
                            type="button"
                            @click="currencySheetOpen = true"
                            class="w-full border-b border-gray-100 py-3.5 text-left"
                        >
                            <p class="mb-2 text-base font-bold text-gray-900">{{ __('member.wallet.currency') }}</p>
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-base" :class="currencyValue ? 'font-medium text-gray-900' : 'text-gray-400'" x-text="currencyValue || @js(__('member.wallet.currency_placeholder'))"></p>
                                <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
                            </div>
                        </button>
                    </template>

                    <template x-if="selectedMethod?.type === 'crypto'">
                        <div>
                            <button type="button" @click="networkSheetOpen = true" class="w-full border-b border-gray-100 py-3.5 text-left">
                                <p class="mb-2 text-base font-bold text-gray-900">{{ __('member.wallet.blockchain_network') }}</p>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-base" :class="networkValue ? 'font-medium text-gray-900' : 'text-gray-400'" x-text="networkValue || @js(__('member.wallet.network_placeholder'))"></p>
                                    <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
                                </div>
                            </button>

                            <x-member.form-field :label="__('member.wallet.crypto_address')">
                                <input
                                    type="text"
                                    name="crypto_address"
                                    x-model="cryptoAddress"
                                    placeholder="{{ __('member.wallet.crypto_address_placeholder') }}"
                                    class="portal-plain-input"
                                >
                            </x-member.form-field>
                        </div>
                    </template>

                    <template x-if="selectedMethod?.type === 'bank'">
                        <div>
                            <x-member.form-field :label="__('member.wallet.bank_account_name')">
                                <input
                                    type="text"
                                    name="bank_account_name"
                                    x-model="bankAccountName"
                                    placeholder="{{ __('member.wallet.bank_account_name_placeholder') }}"
                                    class="portal-plain-input"
                                >
                            </x-member.form-field>

                            <x-member.form-field :label="__('member.wallet.bank_name')">
                                <input
                                    type="text"
                                    name="bank_name"
                                    x-model="bankName"
                                    placeholder="{{ __('member.wallet.bank_name_placeholder') }}"
                                    class="portal-plain-input"
                                >
                            </x-member.form-field>

                            <x-member.form-field :label="__('member.wallet.bank_account_number')">
                                <input
                                    type="text"
                                    name="bank_account_number"
                                    x-model="bankAccountNumber"
                                    placeholder="{{ __('member.wallet.bank_account_number_placeholder') }}"
                                    class="portal-plain-input"
                                >
                            </x-member.form-field>
                        </div>
                    </template>

                    <x-member.form-field :label="__('member.wallet.amount')" label-color="text-orange-500" class="border-b-0">
                        <div class="flex items-center gap-3">
                            <input
                                type="text"
                                inputmode="decimal"
                                name="amount"
                                x-model="amount"
                                placeholder="{{ __('member.wallet.amount_placeholder') }}"
                                class="portal-plain-input flex-1"
                            >
                            <button type="button" @click="fillAll()" class="shrink-0 text-base font-medium text-red-600">{{ __('member.wallet.withdraw_all') }}</button>
                        </div>
                    </x-member.form-field>

                    <div class="space-y-2 border-t border-gray-100 py-3.5">
                        <p class="text-base">
                            <span class="font-medium text-teal-600">{{ __('member.wallet.current_balance') }}:</span>
                            <span class="font-semibold text-teal-600" x-text="money(balance)"></span>
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <p class="text-base">
                                <span class="font-medium text-red-600">{{ __('member.wallet.net_amount') }}:</span>
                                <span class="font-semibold text-red-600" x-text="money(netAmount)"></span>
                            </p>
                            <p class="text-base">
                                <span class="font-medium text-teal-600">{{ __('member.wallet.fee') }}:</span>
                                <span class="font-semibold text-teal-600" x-text="feeDisplay"></span>
                            </p>
                        </div>
                    </div>

                    <x-member.form-field :label="__('member.wallet.withdraw_password')" class="border-t border-gray-100">
                        <input
                            type="password"
                            name="payment_password"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="off"
                            required
                            x-model="paymentPassword"
                            placeholder="{{ __('member.payment_password.placeholder') }}"
                            class="portal-plain-input"
                        >
                    </x-member.form-field>
                </section>

                <button type="submit" class="h-12 w-full rounded-xl bg-black text-base font-medium text-white hover:bg-gray-800 disabled:opacity-60" :disabled="!canSubmit">
                    {{ __('member.wallet.submit_withdraw') }}
                </button>
            </form>
        </div>

        <x-member.bottom-sheet show="methodSheetOpen" :title="__('member.wallet.withdraw_method')">
            <template x-for="method in methods" :key="method.id">
                <button type="button" @click="pickMethod(method)" class="flex w-full items-center justify-between rounded-xl px-2 py-3.5 text-left hover:bg-gray-50">
                    <span class="text-base font-medium text-gray-900" x-text="method.name"></span>
                    <span x-show="selectedMethod?.id === method.id" class="text-emerald-600">✓</span>
                </button>
            </template>
        </x-member.bottom-sheet>

        <x-member.bottom-sheet show="currencySheetOpen" :title="__('member.wallet.currency')">
            <template x-for="currency in availableCurrencies" :key="currency">
                <button type="button" @click="pickCurrency(currency)" class="flex w-full items-center justify-between rounded-xl px-2 py-3.5 text-left hover:bg-gray-50">
                    <span class="text-base font-medium text-gray-900" x-text="currency"></span>
                    <span x-show="currencyValue === currency" class="text-emerald-600">✓</span>
                </button>
            </template>
        </x-member.bottom-sheet>

        <x-member.bottom-sheet show="networkSheetOpen" :title="__('member.wallet.blockchain_network')">
            <template x-for="network in availableNetworks" :key="network.label">
                <button type="button" @click="pickNetwork(network)" class="flex w-full items-center justify-between rounded-xl px-2 py-3.5 text-left hover:bg-gray-50">
                    <span class="text-base font-medium text-gray-900" x-text="network.label"></span>
                    <span x-show="networkValue === network.label" class="text-emerald-600">✓</span>
                </button>
            </template>
        </x-member.bottom-sheet>

    </div>
@endsection

@push('scripts')
<script>
function withdrawalForm(config) {
    return {
        methods: config.methods,
        balance: config.balance,
        selectedMethod: null,
        currencyValue: config.oldCurrency || '',
        networkValue: config.oldNetwork || '',
        cryptoAddress: config.oldCryptoAddress || '',
        bankAccountName: config.oldBankAccountName || '',
        bankName: config.oldBankName || '',
        bankAccountNumber: config.oldBankAccountNumber || '',
        amount: config.oldAmount || '',
        paymentPassword: '',
        methodSheetOpen: false,
        currencySheetOpen: false,
        networkSheetOpen: false,
        init() {
            if (config.oldMethodId) {
                this.selectedMethod = this.methods.find((method) => method.id === Number(config.oldMethodId)) || null;
                if (this.selectedMethod?.type === 'bank' && !this.currencyValue) {
                    this.currencyValue = this.selectedMethod.currency || 'VND';
                }
            }
        },
        get availableCurrencies() {
            return this.selectedMethod?.currencies || [];
        },
        get availableNetworks() {
            return this.selectedMethod?.networks || [];
        },
        get selectedMethodId() {
            if (!this.selectedMethod) {
                return null;
            }
            if (this.selectedMethod.type !== 'crypto') {
                return this.selectedMethod.id;
            }
            const network = this.availableNetworks.find((item) => item.label === this.networkValue);
            return network?.method_id || this.selectedMethod.id || null;
        },
        get feePercent() {
            if (!this.selectedMethod) return 0;
            if (this.selectedMethod.type === 'crypto') {
                const network = this.availableNetworks.find((item) => item.label === this.networkValue);
                return Number(network?.fee || 0);
            }
            return Number(this.selectedMethod.fee_percent || 0);
        },
        get feeAmount() {
            const amount = parseFloat(this.amount) || 0;
            return amount * this.feePercent / 100;
        },
        get netAmount() {
            const amount = parseFloat(this.amount) || 0;
            return Math.max(0, amount - this.feeAmount);
        },
        get feeDisplay() {
            if (this.feePercent <= 0 || this.feeAmount <= 0) {
                return '0';
            }
            return this.money(this.feeAmount);
        },
        get canSubmit() {
            if (!this.selectedMethod || !this.selectedMethodId || (parseFloat(this.amount) || 0) <= 0 || this.paymentPassword.length !== 6) {
                return false;
            }

            if (this.selectedMethod.type === 'bank') {
                return Boolean(this.bankAccountName && this.bankName && this.bankAccountNumber);
            }

            return Boolean(this.currencyValue && this.networkValue && this.cryptoAddress);
        },
        money(value) {
            return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        pickMethod(method) {
            this.selectedMethod = method;
            this.currencyValue = method.type === 'bank' ? (method.currency || 'VND') : '';
            this.networkValue = '';
            this.cryptoAddress = '';
            this.bankAccountName = '';
            this.bankName = '';
            this.bankAccountNumber = '';
            this.methodSheetOpen = false;
        },
        pickCurrency(currency) {
            this.currencyValue = currency;
            this.currencySheetOpen = false;
        },
        pickNetwork(network) {
            this.networkValue = network.label;
            if (network.currency) {
                this.currencyValue = network.currency;
            }
            this.networkSheetOpen = false;
        },
        fillAll() {
            this.amount = String(this.balance);
        },
    };
}
</script>
@endpush
