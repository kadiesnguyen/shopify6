@extends('layouts.member')

@section('title', __('member.wallet.recharge_title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div
        x-data="rechargeForm({
            methods: @js($methods),
            balance: @js($balance),
            supportUrl: @js(route('member.chat.index')),
            oldMethodId: @js(old('recharge_method_id')),
            oldAmount: @js(old('amount')),
            copyLabel: @js(__('member.wallet.copy_address')),
            copiedLabel: @js(__('member.wallet.address_copied')),
        })"
        class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(6rem+env(safe-area-inset-bottom))]"
    >
        <header class="sticky top-0 z-10 bg-black text-white">
            <div class="relative flex items-center justify-between px-4 py-3">
                <a href="{{ route('member.my.index') }}" class="flex items-center gap-1.5 text-white/90 no-underline">
                    <x-member.icon name="chevron-left" class="size-5" />
                    <span class="text-sm font-medium">{{ __('member.back') }}</span>
                </a>
                <span class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.wallet.recharge_title') }}</span>
                <a href="{{ route('member.wallet.fund-records', ['type' => 'recharge']) }}" class="text-sm font-medium text-white/90 no-underline">{{ __('member.wallet.history') }}</a>
            </div>
        </header>

        <div class="p-4">
            @if ($errors->any())
                <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('member.wallet.recharge.store') }}" class="portal-wallet-form space-y-4">
                @csrf
                <input type="hidden" name="recharge_method_id" :value="selectedMethodId ?? ''">
                <input type="hidden" name="currency" :value="currencyValue">
                <input type="hidden" name="network" :value="networkValue">

                <section class="rounded-xl bg-white px-4 py-1 shadow-sm">
                    <button type="button" @click="methodSheetOpen = true" class="w-full border-b border-gray-100 py-3.5 text-left">
                        <p class="mb-2 text-base font-bold text-red-600">{{ __('member.wallet.recharge_method') }}</p>
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-base" :class="selectedMethod ? 'font-medium text-gray-900' : 'text-gray-400'" x-text="selectedMethod?.name ?? @js(__('member.wallet.recharge_method_placeholder'))"></p>
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

                            <template x-if="networkValue && walletAddress">
                                <div class="border-b border-gray-100 py-3.5">
                                    <p class="mb-2 text-base font-bold text-gray-900">{{ __('member.wallet.crypto_address') }}</p>
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="break-all text-base font-medium text-gray-900" x-text="walletAddress"></p>
                                        <button
                                            type="button"
                                            @click="copyAddress()"
                                            class="shrink-0 text-sm font-medium text-emerald-600"
                                            x-text="copyButtonLabel"
                                        ></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="selectedMethod?.type === 'bank'">
                        <div>
                            <p class="border-b border-gray-100 py-3 text-base font-bold text-gray-900">{{ __('member.wallet.transfer_info') }}</p>
                            <x-member.form-field :label="__('member.wallet.bank_account_name')">
                                <p class="text-base font-medium text-gray-900" x-text="selectedMethod?.bank_account_name || '—'"></p>
                            </x-member.form-field>
                            <x-member.form-field :label="__('member.wallet.bank_name')">
                                <p class="text-base font-medium text-gray-900" x-text="selectedMethod?.bank_name || '—'"></p>
                            </x-member.form-field>
                            <x-member.form-field :label="__('member.wallet.bank_account_number')">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-base font-medium text-gray-900" x-text="selectedMethod?.bank_account_number || '—'"></p>
                                    <button
                                        type="button"
                                        x-show="selectedMethod?.bank_account_number"
                                        @click="copyText(selectedMethod.bank_account_number)"
                                        class="shrink-0 text-sm font-medium text-emerald-600"
                                        x-text="copyButtonLabel"
                                    ></button>
                                </div>
                            </x-member.form-field>
                        </div>
                    </template>

                    <x-member.form-field :label="__('member.wallet.amount')" label-color="text-orange-500" class="border-b-0">
                        <input
                            type="text"
                            inputmode="decimal"
                            name="amount"
                            x-model="amount"
                            placeholder="{{ __('member.wallet.amount_placeholder') }}"
                            class="portal-plain-input"
                        >
                    </x-member.form-field>

                    <div class="border-t border-gray-100 py-3.5">
                        <p class="text-base">
                            <span class="font-medium text-teal-600">{{ __('member.wallet.current_balance') }}:</span>
                            <span class="font-semibold text-teal-600" x-text="money(balance)"></span>
                        </p>
                    </div>
                </section>

                <button type="submit" class="h-12 w-full rounded-xl bg-black text-base font-medium text-white hover:bg-gray-800 disabled:opacity-60" :disabled="!canSubmit">
                    {{ __('member.wallet.submit_recharge') }}
                </button>
            </form>
        </div>

        <x-member.bottom-sheet show="methodSheetOpen" :title="__('member.wallet.recharge_method')">
            <template x-for="method in methods" :key="method.id">
                <button type="button" @click="pickMethod(method)" class="flex w-full items-center justify-between rounded-xl px-2 py-3.5 text-left hover:bg-gray-50">
                    <span class="truncate text-base font-medium text-gray-900" x-text="method.name"></span>
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
function rechargeForm(config) {
    return {
        methods: config.methods,
        balance: config.balance,
        selectedMethod: null,
        currencyValue: '',
        networkValue: '',
        amount: config.oldAmount || '',
        methodSheetOpen: false,
        currencySheetOpen: false,
        networkSheetOpen: false,
        copyButtonLabel: config.copyLabel,
        init() {
            if (config.oldMethodId) {
                this.selectedMethod = this.methods.find((method) => method.id === Number(config.oldMethodId)) || null;
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
        get walletAddress() {
            const network = this.availableNetworks.find((item) => item.label === this.networkValue);
            return network?.wallet_address || '';
        },
        get canSubmit() {
            const amount = (parseFloat(this.amount) || 0) > 0;
            if (!this.selectedMethod || !amount || !this.selectedMethodId) {
                return false;
            }

            if (this.selectedMethod.type !== 'crypto') {
                return true;
            }

            return Boolean(this.currencyValue && this.networkValue);
        },
        money(value) {
            return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        pickMethod(method) {
            this.methodSheetOpen = false;

            if (method.status !== 'active') {
                window.location.href = config.supportUrl;

                return;
            }

            this.selectedMethod = method;
            this.currencyValue = '';
            this.networkValue = '';
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
        copyAddress() {
            this.copyText(this.walletAddress);
        },
        copyText(value) {
            if (!value || !navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(value).then(() => {
                this.copyButtonLabel = config.copiedLabel;
                setTimeout(() => {
                    this.copyButtonLabel = config.copyLabel;
                }, 1500);
            });
        },
    };
}
</script>
@endpush
