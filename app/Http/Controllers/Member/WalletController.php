<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberRechargeRequest;
use App\Http\Requests\Member\MemberWithdrawalRequest;
use App\Models\RechargeMethod;
use App\Models\RechargeRequest;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawalRequest;
use App\Services\Member\WithdrawalQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(private readonly WithdrawalQuoteService $withdrawalQuote) {}

    public function recharge(): View
    {
        $methods = $this->mergeCryptoMethods(
            RechargeMethod::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (RechargeMethod $method): array => $this->mapRechargeMethod($method)),
            __('member.wallet.crypto_method_name'),
        )->values();

        $balance = (float) (auth()->user()->wallet?->balance ?? 0);

        return view('member.wallet.recharge', compact('methods', 'balance'));
    }

    /** @return array<string, mixed> */
    private function mapRechargeMethod(RechargeMethod $method): array
    {
        $config = $method->config ?? [];
        $currencies = collect($config['currencies'] ?? [])
            ->map(fn ($currency): string => trim((string) $currency))
            ->filter()
            ->values()
            ->all();
        $fallbackCurrency = count($currencies) === 1 ? $currencies[0] : $this->inferCurrencyFromMethodName((string) $method->name);

        $networks = collect($config['networks'] ?? [])->map(function ($network) use ($config, $fallbackCurrency, $method): array {
            $rawLabel = '';
            if (is_array($network)) {
                $rawLabel = trim((string) ($network['label'] ?? ''));
                $walletAddress = (string) ($network['wallet_address'] ?? ($config['wallet_address'] ?? ''));
            } else {
                $rawLabel = trim((string) $network);
                $walletAddress = (string) ($config['wallet_address'] ?? '');
            }

            $label = $this->normalizeNetworkLabel($rawLabel, $fallbackCurrency);
            $currency = $this->extractCurrencyFromNetworkLabel($label) ?? $fallbackCurrency;

            return [
                'label' => $label,
                'currency' => $currency,
                'wallet_address' => $walletAddress,
                'method_id' => $method->id,
            ];
        })->filter(fn (array $network): bool => $network['label'] !== '')->values()->all();

        if ($networks === [] && filled($config['network'] ?? null)) {
            $label = $this->normalizeNetworkLabel((string) $config['network'], $fallbackCurrency);
            $networks = [[
                'label' => $label,
                'currency' => $this->extractCurrencyFromNetworkLabel($label) ?? $fallbackCurrency,
                'wallet_address' => $config['wallet_address'] ?? '',
                'method_id' => $method->id,
            ]];
        }

        if ($networks === [] && $method->type === RechargeMethod::TYPE_CRYPTO) {
            $walletAddress = (string) ($config['wallet_address'] ?? '');
            $networks = collect(config('wallet_data.blockchain_networks', []))
                ->map(function ($network) use ($walletAddress, $fallbackCurrency, $method): array {
                    $label = $this->normalizeNetworkLabel((string) $network, $fallbackCurrency);

                    return [
                        'label' => $label,
                        'currency' => $this->extractCurrencyFromNetworkLabel($label) ?? $fallbackCurrency,
                        'wallet_address' => $walletAddress,
                        'method_id' => $method->id,
                    ];
                })
                ->values()
                ->all();
        }

        if ($currencies === []) {
            $currencies = collect($networks)
                ->pluck('currency')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [
            'id' => $method->id,
            'name' => $method->name,
            'type' => $method->type,
            'status' => $method->status,
            'currencies' => $currencies !== [] ? $currencies : ['USDT'],
            'networks' => $networks,
            'bank_account_name' => $config['bank_account_name'] ?? null,
            'bank_name' => $config['bank_name'] ?? null,
            'bank_account_number' => $config['bank_account_number'] ?? null,
        ];
    }

    public function storeRecharge(MemberRechargeRequest $request): RedirectResponse
    {
        RechargeRequest::query()->create([
            'user_id' => auth()->id(),
            'recharge_method_id' => $request->validated('recharge_method_id'),
            'amount' => $request->validated('amount'),
            'status' => RechargeRequest::STATUS_PENDING,
            'reference' => 'RC-'.strtoupper(substr(uniqid(), -8)),
        ]);

        return redirect()
            ->route('member.wallet.fund-records', ['type' => 'recharge'])
            ->with('status', __('member.wallet.recharge_success'));
    }

    public function withdrawal(): View
    {
        $methods = $this->mergeCryptoMethods(
            WithdrawalMethod::query()
            ->where('status', WithdrawalMethod::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (WithdrawalMethod $method): array => $this->mapWithdrawalMethod($method)),
            __('member.wallet.crypto_method_name'),
        )->values();

        $wallet = auth()->user()->wallet;
        $balance = (float) ($wallet?->balance ?? 0);

        return view('member.wallet.withdrawal', compact('methods', 'balance'));
    }

    /** @return array<string, mixed> */
    private function mapWithdrawalMethod(WithdrawalMethod $method): array
    {
        $config = $method->config ?? [];
        $feePercent = (float) ($config['fee_percent'] ?? 0);
        $currencies = collect($config['currencies'] ?? [])
            ->map(fn ($currency): string => trim((string) $currency))
            ->filter()
            ->values()
            ->all();

        if ($currencies === [] && filled($config['currency'] ?? null)) {
            $currencies = [trim((string) $config['currency'])];
        }

        $fallbackCurrency = count($currencies) === 1 ? $currencies[0] : $this->inferCurrencyFromMethodName((string) $method->name);

        $networks = collect($config['networks'] ?? [])->map(function ($network) use ($feePercent, $fallbackCurrency, $method): array {
            $rawLabel = '';
            if (is_array($network)) {
                $rawLabel = trim((string) ($network['label'] ?? ''));
                $networkFee = (float) ($network['fee'] ?? $feePercent);
            } else {
                $rawLabel = trim((string) $network);
                $networkFee = $feePercent;
            }

            $label = $this->normalizeNetworkLabel($rawLabel, $fallbackCurrency);
            $currency = $this->extractCurrencyFromNetworkLabel($label) ?? $fallbackCurrency;

            return [
                'label' => $label,
                'currency' => $currency,
                'fee' => $networkFee,
                'method_id' => $method->id,
            ];
        })->filter(fn (array $network): bool => $network['label'] !== '')->values()->all();

        if ($networks === [] && $method->type === WithdrawalMethod::TYPE_CRYPTO) {
            $networks = collect(config('wallet_data.blockchain_networks', []))
                ->map(function ($network) use ($feePercent, $fallbackCurrency, $method): array {
                    $label = $this->normalizeNetworkLabel((string) $network, $fallbackCurrency);

                    return [
                        'label' => $label,
                        'currency' => $this->extractCurrencyFromNetworkLabel($label) ?? $fallbackCurrency,
                        'fee' => $feePercent,
                        'method_id' => $method->id,
                    ];
                })
                ->values()
                ->all();
        }

        if ($currencies === []) {
            $currencies = collect($networks)
                ->pluck('currency')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [
            'id' => $method->id,
            'name' => $method->name,
            'type' => $method->type,
            'status' => $method->status,
            'fee_percent' => $feePercent,
            'currency' => $config['currency'] ?? ($currencies[0] ?? 'VND'),
            'currencies' => $currencies !== [] ? $currencies : ['USDT'],
            'networks' => $networks,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $methods
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeCryptoMethods(Collection $methods, string $displayName): Collection
    {
        $cryptoMethods = $methods->where('type', RechargeMethod::TYPE_CRYPTO)->values();
        $normalMethods = $methods->where('type', '!=', RechargeMethod::TYPE_CRYPTO)->values();

        if ($cryptoMethods->isEmpty()) {
            return $methods->values();
        }

        $cryptoNetworks = $cryptoMethods
            ->flatMap(function (array $method): array {
                $status = (string) ($method['status'] ?? RechargeMethod::STATUS_ACTIVE);

                return collect($method['networks'] ?? [])
                    ->map(function (array $network) use ($method, $status): array {
                        return [
                            'label' => (string) ($network['label'] ?? ''),
                            'currency' => (string) ($network['currency'] ?? $this->extractCurrencyFromNetworkLabel((string) ($network['label'] ?? '')) ?? ''),
                            'wallet_address' => (string) ($network['wallet_address'] ?? ''),
                            'fee' => (float) ($network['fee'] ?? 0),
                            'method_id' => (int) ($network['method_id'] ?? $method['id']),
                            'status' => $status,
                        ];
                    })
                    ->filter(fn (array $network): bool => $network['label'] !== '')
                    ->all();
            })
            ->sortBy(fn (array $network): int => $network['status'] === RechargeMethod::STATUS_ACTIVE ? 0 : 1)
            ->unique('label')
            ->values();

        $currencies = $cryptoNetworks
            ->pluck('currency')
            ->merge($cryptoMethods->flatMap(fn (array $method): array => (array) ($method['currencies'] ?? [])))
            ->filter()
            ->unique()
            ->values();

        $activeCrypto = $cryptoMethods->first(fn (array $method): bool => ($method['status'] ?? '') === RechargeMethod::STATUS_ACTIVE);
        $defaultCrypto = $activeCrypto ?? $cryptoMethods->first();
        $cryptoStatus = $activeCrypto ? RechargeMethod::STATUS_ACTIVE : RechargeMethod::STATUS_INACTIVE;

        $mergedCrypto = [
            'id' => $defaultCrypto['id'] ?? null,
            'name' => $displayName,
            'type' => RechargeMethod::TYPE_CRYPTO,
            'status' => $cryptoStatus,
            'fee_percent' => (float) ($defaultCrypto['fee_percent'] ?? 0),
            'currency' => $defaultCrypto['currency'] ?? null,
            'currencies' => $currencies->all(),
            'networks' => $cryptoNetworks->map(function (array $network): array {
                return [
                    'label' => $network['label'],
                    'currency' => $network['currency'],
                    'wallet_address' => $network['wallet_address'],
                    'fee' => $network['fee'],
                    'method_id' => $network['method_id'],
                ];
            })->all(),
        ];

        return collect([$mergedCrypto])->merge($normalMethods)->values();
    }

    private function normalizeNetworkLabel(string $label, ?string $fallbackCurrency = null): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        if (preg_match('/^.+\(.+\)$/', $label)) {
            return $label;
        }

        return $fallbackCurrency ? $fallbackCurrency.' ('.$label.')' : $label;
    }

    private function extractCurrencyFromNetworkLabel(string $label): ?string
    {
        if (! preg_match('/^([^(]+)\(.+\)$/', str_replace(' ', '', $label), $matches)) {
            return null;
        }

        return trim((string) $matches[1]);
    }

    private function inferCurrencyFromMethodName(string $name): ?string
    {
        if (preg_match('/^(USDT|USDC|BTC|ETH)\b/i', trim($name), $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    public function storeWithdrawal(MemberWithdrawalRequest $request): RedirectResponse
    {
        $wallet = auth()->user()->wallet;
        $amount = (float) $request->validated('amount');
        $method = WithdrawalMethod::query()->findOrFail($request->validated('withdrawal_method_id'));

        if (! $wallet || $wallet->balance < $amount) {
            return back()
                ->withInput()
                ->withErrors(['amount' => __('member.wallet.insufficient_balance')]);
        }

        $network = $request->validated('network');
        $quote = $this->withdrawalQuote->quote($method, $amount, $network);

        if ($method->type === WithdrawalMethod::TYPE_BANK) {
            $currency = $method->config['currency'] ?? 'VND';
            $address = implode(' · ', array_filter([
                $request->validated('bank_account_name'),
                $request->validated('bank_name'),
                $request->validated('bank_account_number'),
            ]));
        } else {
            $currency = $request->validated('currency');
            $address = $request->validated('crypto_address');
        }

        WithdrawalRequest::query()->create([
            'user_id' => auth()->id(),
            'withdrawal_method_id' => $method->id,
            'amount' => $amount,
            'status' => WithdrawalRequest::STATUS_PENDING,
            'payout_details' => [
                'method_name' => $method->name,
                'type' => $method->type,
                'currency' => $currency,
                'network' => $network,
                'address' => $address,
                'fee_percent' => $quote['fee_percent'],
                'fee_amount' => $quote['fee_amount'],
                'net_amount' => $quote['net_amount'],
            ],
        ]);

        return redirect()
            ->route('member.wallet.fund-records', ['type' => 'withdrawal'])
            ->with('status', __('member.wallet.withdraw_success'));
    }

    public function withdrawalRecords(Request $request): RedirectResponse
    {
        return redirect()->route('member.wallet.fund-records', ['type' => 'withdrawal']);
    }

    public function fundRecords(Request $request): View
    {
        $type = $request->query('type', 'recharge');

        if (! in_array($type, ['recharge', 'withdrawal'], true)) {
            $type = 'recharge';
        }

        if ($type === 'recharge') {
            $records = RechargeRequest::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } else {
            $records = WithdrawalRequest::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return view('member.wallet.fund-records', compact('records', 'type'));
    }
}
