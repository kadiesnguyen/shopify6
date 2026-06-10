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

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bank,crypto'],
            'network_or_bank' => ['nullable', 'string', 'max:120'],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function configFrom(array $data): array
    {
        return array_filter([
            'network_or_bank' => $data['network_or_bank'] ?? null,
            'fee_percent' => isset($data['fee_percent']) ? (float) $data['fee_percent'] : null,
            'internal_note' => $data['internal_note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
