@php($isEdit = $method->exists)
@php($config = $method->config ?? [])
@php($closeUrl = route('admin.withdrawal-methods.index'))
@php($selectedType = old('type', $method->type ?? 'bank'))
@php($blockchainNetworks = config('wallet_data.blockchain_networks', []))
@php($currentNetworkLabels = collect($config['networks'] ?? [])->map(fn ($network) => is_array($network) ? ($network['label'] ?? '') : (string) $network)->filter()->values()->all())
@php($selectedNetworks = collect(old('networks', $currentNetworkLabels))->map(fn ($item) => trim((string) $item))->filter()->values()->all())
@php($networkOrBank = old('network_or_bank', $config['network_or_bank'] ?? ''))
@php($cryptoOptions = collect(config('wallet_data.sieummo_withdrawal_methods', []))
    ->map(function ($item): array {
        $currency = trim((string) ($item['currency'] ?? ''));
        $network = trim((string) ($item['network'] ?? ''));

        return [
            'currency' => $currency,
            'network' => $network,
            'label' => $currency !== '' && $network !== '' ? $currency.' ('.$network.')' : '',
        ];
    })
    ->filter(fn (array $option): bool => $option['label'] !== '')
    ->unique(fn (array $option): string => $option['label'])
    ->values())
@php($cryptoCurrencies = $cryptoOptions->pluck('currency')->filter()->unique()->values()->all())
@php($selectedCurrency = trim((string) old('currencies', collect((array) ($config['currencies'] ?? []))->first() ?? $method->name)))
@php($selectedNetwork = trim((string) (old('networks.0') ?? $selectedNetworks[0] ?? '')))
@php($networkOptions = $cryptoOptions->pluck('label')->merge($blockchainNetworks)->merge([$selectedNetwork])->filter()->unique()->values()->all())

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
    <div class="h-[80vh] max-h-[80vh] w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl">
        <div
            class="flex h-full flex-col p-4 sm:p-5"
            x-data="{
                type: @js($selectedType),
                currencyOptions: @js($cryptoCurrencies),
                currencyQuery: @js($selectedCurrency),
                selectedCurrency: @js($selectedCurrency),
                currencyOpen: false,
                networkOptions: @js($networkOptions),
                networkQuery: @js($selectedNetwork),
                selectedNetwork: @js($selectedNetwork),
                networkOpen: false,
                init() {
                    if (!this.selectedCurrency && this.selectedNetwork) {
                        const match = this.selectedNetwork.match(/^(.+?)\s*\((.+)\)$/);
                        if (match) {
                            this.selectedCurrency = match[1];
                            this.currencyQuery = match[1];
                        }
                    }
                },
                get filteredCurrencies() {
                    const query = this.currencyQuery.trim().toLowerCase();
                    if (!query) return this.currencyOptions;
                    return this.currencyOptions.filter((currency) => currency.toLowerCase().includes(query));
                },
                get filteredNetworks() {
                    const query = this.networkQuery.trim().toLowerCase();
                    if (!query) return this.networkOptions;
                    return this.networkOptions.filter((network) => network.toLowerCase().includes(query));
                },
                pickCurrency(currency) {
                    this.selectedCurrency = currency;
                    this.currencyQuery = currency;
                    this.currencyOpen = false;
                },
                pickNetwork(network) {
                    this.selectedNetwork = network;
                    this.networkQuery = network;
                    this.networkOpen = false;
                    const match = network.match(/^(.+?)\s*\((.+)\)$/);
                    if (match) {
                        this.selectedCurrency = match[1];
                        this.currencyQuery = match[1];
                    }
                },
            }"
        >
            <div class="mb-4 flex shrink-0 items-center justify-between">
                <h3 class="text-lg font-semibold">{{ $isEdit ? __('admin.methods.edit_method') : __('admin.methods.add_method') }}</h3>
                <a href="{{ $closeUrl }}" class="text-2xl leading-none text-slate-400 hover:text-slate-600">×</a>
            </div>

            <form method="POST" action="{{ $isEdit ? route('admin.withdrawal-methods.update', $method) : route('admin.withdrawal-methods.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @if ($isEdit)
                @method('PATCH')
            @endif

            <div class="space-y-3 overflow-y-auto pr-1">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.type') }}</label>
                    <select name="type" x-model="type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="bank" @selected($selectedType === 'bank')>{{ __('admin.methods.type_bank') }}</option>
                        <option value="crypto" @selected($selectedType === 'crypto')>{{ __('admin.methods.type_crypto') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.name') }} *</label>
                    <input name="name" value="{{ old('name', $method->name) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                </div>

                <div x-show="type === 'crypto'" x-cloak class="space-y-4">
                    <div class="relative" @click.outside="currencyOpen = false">
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.currencies') }}</label>
                        <input
                            x-model="currencyQuery"
                            @focus="currencyOpen = true"
                            @input="currencyOpen = true"
                            type="text"
                            placeholder="{{ __('admin.methods.search_currency') }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                            autocomplete="off"
                        >
                        <input type="hidden" name="currencies" :value="selectedCurrency" :disabled="type !== 'crypto'">
                        <div
                            x-show="currencyOpen"
                            x-cloak
                            class="absolute z-20 mt-1 max-h-44 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                        >
                            <template x-if="filteredCurrencies.length === 0">
                                <p class="px-2 py-1.5 text-xs text-slate-500">{{ __('admin.methods.search_no_results') }}</p>
                            </template>
                            <template x-for="currency in filteredCurrencies" :key="currency">
                                <button
                                    type="button"
                                    @click="pickCurrency(currency)"
                                    class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-slate-100"
                                    x-text="currency"
                                ></button>
                            </template>
                        </div>
                    </div>
                    <div class="relative" @click.outside="networkOpen = false">
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.networks') }}</label>
                        <input
                            x-model="networkQuery"
                            @focus="networkOpen = true"
                            @input="networkOpen = true"
                            type="text"
                            placeholder="{{ __('admin.methods.search_network') }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                            autocomplete="off"
                        >
                        <input type="hidden" name="networks[]" :value="selectedNetwork" :disabled="type !== 'crypto'">
                        <div
                            x-show="networkOpen"
                            x-cloak
                            class="absolute z-20 mt-1 max-h-44 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                        >
                            <template x-if="filteredNetworks.length === 0">
                                <p class="px-2 py-1.5 text-xs text-slate-500">{{ __('admin.methods.search_no_results') }}</p>
                            </template>
                            <template x-for="network in filteredNetworks" :key="network">
                                <button
                                    type="button"
                                    @click="pickNetwork(network)"
                                    class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-slate-100"
                                    x-text="network"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-show="type === 'bank'" x-cloak>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.network_or_bank') }}</label>
                    <input
                        name="network_or_bank"
                        value="{{ $networkOrBank }}"
                        placeholder="{{ __('member.wallet.bank_name_placeholder') }}"
                        class="w-full rounded-lg border-slate-300 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.withdrawal_fee_percent') }}</label>
                    <input type="number" step="0.01" min="0" max="100" name="fee_percent" value="{{ old('fee_percent', $config['fee_percent'] ?? '') }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>

                <div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.sort_order') }}</label>
                            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.enabled') }}</label>
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="active" @selected(old('status', $method->status ?? 'active') === 'active')>{{ __('admin.methods.yes') }}</option>
                                <option value="inactive" @selected(old('status', $method->status ?? 'active') === 'inactive')>{{ __('admin.methods.no') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.internal_note') }}</label>
                    <textarea name="internal_note" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ old('internal_note', $config['internal_note'] ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-4 flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-white pt-4">
                    <a href="{{ $closeUrl }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            </div>
        </form>
        </div>
    </div>
</div>
