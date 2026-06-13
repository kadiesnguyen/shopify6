<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RechargeMethodController extends Controller
{
    public function index(Request $request): View
    {
        $methods = RechargeMethod::query()->orderBy('sort_order')->paginate(15);

        $modalMethod = null;
        $showMethodModal = $request->boolean('show_create') || $request->filled('edit');

        if ($request->filled('edit')) {
            $modalMethod = RechargeMethod::query()->find($request->integer('edit'));
        }

        return view('admin.recharge-methods.index', compact('methods', 'modalMethod', 'showMethodModal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        RechargeMethod::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => $this->configFrom($data),
        ]);

        return redirect()->route('admin.recharge-methods.index')->with('status', __('admin.methods.saved'));
    }

    public function update(Request $request, RechargeMethod $rechargeMethod): RedirectResponse
    {
        $data = $this->validated($request);

        $rechargeMethod->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => $this->configFrom($data),
        ]);

        return redirect()->route('admin.recharge-methods.index')->with('status', __('admin.methods.updated'));
    }

    public function destroy(RechargeMethod $rechargeMethod): RedirectResponse
    {
        $rechargeMethod->delete();

        return back()->with('status', __('admin.methods.deleted'));
    }

    public function toggleStatus(RechargeMethod $rechargeMethod): RedirectResponse
    {
        $enabled = $rechargeMethod->status !== RechargeMethod::STATUS_ACTIVE;

        $rechargeMethod->update([
            'status' => $enabled ? RechargeMethod::STATUS_ACTIVE : RechargeMethod::STATUS_INACTIVE,
        ]);

        return redirect()->route('admin.recharge-methods.index')->with(
            'status',
            $enabled ? __('admin.methods.recharge_enabled') : __('admin.methods.recharge_disabled'),
        );
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bank,crypto'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:120'],
            'wallet_address' => ['nullable', 'string', 'max:255'],
            'currencies' => ['nullable', 'string', 'max:255'],
            'networks' => ['nullable', 'array', 'min:1'],
            'networks.*' => ['required', 'string', 'max:120', 'in:'.implode(',', $this->allowedNetworkLabels())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($request->input('type') === RechargeMethod::TYPE_BANK) {
            $rules['bank_account_name'][0] = 'required';
            $rules['bank_name'][0] = 'required';
            $rules['bank_account_number'][0] = 'required';
            unset($rules['networks'], $rules['networks.*']);
        }

        if ($request->input('type') === RechargeMethod::TYPE_CRYPTO) {
            $rules['wallet_address'][0] = 'required';
            $rules['currencies'][0] = 'required';
            $rules['networks'] = ['required', 'array', 'min:1'];
            $rules['networks.*'] = ['required', 'string', 'max:120', 'in:'.implode(',', $this->allowedNetworkLabels())];
        }

        if ($request->input('type') === RechargeMethod::TYPE_BANK) {
            $rules['bank_name'][] = 'in:'.implode(',', config('wallet_data.vietnam_banks', []));
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $data */
    private function configFrom(array $data): array
    {
        if (($data['type'] ?? null) === RechargeMethod::TYPE_BANK) {
            return array_filter([
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'internal_note' => $data['internal_note'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        $currencies = $this->parseCsv((string) ($data['currencies'] ?? ''));
        $networkLabels = array_values(array_unique(array_filter(
            array_map(static fn ($item): string => trim((string) $item), (array) ($data['networks'] ?? [])),
            static fn (string $item): bool => $item !== '',
        )));
        $walletAddress = (string) ($data['wallet_address'] ?? '');

        $networks = array_map(
            fn (string $label): array => ['label' => $label, 'wallet_address' => $walletAddress],
            $networkLabels,
        );

        return array_filter([
            'currencies' => $currencies,
            'networks' => $networks,
            'wallet_address' => $walletAddress,
            'internal_note' => $data['internal_note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** @return list<string> */
    private function parseCsv(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));
        $parts = array_filter($parts, fn (string $item): bool => $item !== '');

        return array_values(array_unique($parts));
    }

    /** @return list<string> */
    private function allowedNetworkLabels(): array
    {
        $plain = collect(config('wallet_data.blockchain_networks', []))
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values();

        $combo = collect(config('wallet_data.sieummo_withdrawal_methods', []))
            ->map(function ($item): string {
                $currency = trim((string) ($item['currency'] ?? ''));
                $network = trim((string) ($item['network'] ?? ''));

                if ($currency === '' || $network === '') {
                    return '';
                }

                return $currency.' ('.$network.')';
            })
            ->filter()
            ->values();

        return $plain->merge($combo)->unique()->values()->all();
    }
}
