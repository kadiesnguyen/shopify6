<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalMethodController extends Controller
{
    public function index(Request $request): View
    {
        $methods = WithdrawalMethod::query()->orderBy('sort_order')->paginate(15);

        $modalMethod = null;
        $showMethodModal = $request->boolean('show_create') || $request->filled('edit');

        if ($request->filled('edit')) {
            $modalMethod = WithdrawalMethod::query()->find($request->integer('edit'));
        }

        return view('admin.withdrawal-methods.index', compact('methods', 'modalMethod', 'showMethodModal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        WithdrawalMethod::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => $this->configFrom($data),
        ]);

        return redirect()->route('admin.withdrawal-methods.index')->with('status', __('admin.methods.saved'));
    }

    public function update(Request $request, WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $data = $this->validated($request);

        $withdrawalMethod->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => array_merge($withdrawalMethod->config ?? [], $this->configFrom($data)),
        ]);

        return redirect()->route('admin.withdrawal-methods.index')->with('status', __('admin.methods.updated'));
    }

    public function destroy(WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $withdrawalMethod->delete();

        return back()->with('status', __('admin.methods.deleted'));
    }

    public function toggleStatus(WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $enabled = $withdrawalMethod->status !== WithdrawalMethod::STATUS_ACTIVE;

        $withdrawalMethod->update([
            'status' => $enabled ? WithdrawalMethod::STATUS_ACTIVE : WithdrawalMethod::STATUS_INACTIVE,
        ]);

        return redirect()->route('admin.withdrawal-methods.index')->with(
            'status',
            $enabled ? __('admin.methods.withdrawal_enabled') : __('admin.methods.withdrawal_disabled'),
        );
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bank,crypto'],
            'currencies' => ['nullable', 'string', 'max:255'],
            'network_or_bank' => ['nullable', 'string', 'max:120'],
            'networks' => ['nullable', 'array', 'min:1'],
            'networks.*' => ['required', 'string', 'max:120', 'in:'.implode(',', $this->allowedNetworkLabels())],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($request->input('type') === WithdrawalMethod::TYPE_BANK) {
            unset($rules['networks'], $rules['networks.*']);
        }

        if ($request->input('type') === WithdrawalMethod::TYPE_CRYPTO) {
            $rules['currencies'][0] = 'required';
            $rules['networks'] = ['required', 'array', 'min:1'];
            $rules['networks.*'] = ['required', 'string', 'max:120', 'in:'.implode(',', $this->allowedNetworkLabels())];
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $data */
    private function configFrom(array $data): array
    {
        $feePercent = isset($data['fee_percent']) ? (float) $data['fee_percent'] : 0.0;
        $currencies = $this->parseCsv((string) ($data['currencies'] ?? ''));
        $networkLabels = array_values(array_unique(array_filter(
            array_map(static fn ($item): string => trim((string) $item), (array) ($data['networks'] ?? [])),
            static fn (string $item): bool => $item !== '',
        )));

        return array_filter([
            'currencies' => $currencies,
            'networks' => array_map(
                static fn (string $label): array => ['label' => $label, 'fee' => $feePercent],
                $networkLabels,
            ),
            'network_or_bank' => $networkLabels[0] ?? ($data['network_or_bank'] ?? null),
            'fee_percent' => $feePercent,
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
