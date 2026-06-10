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
            'config' => array_merge($rechargeMethod->config ?? [], $this->configFrom($data)),
        ]);

        return redirect()->route('admin.recharge-methods.index')->with('status', __('admin.methods.updated'));
    }

    public function destroy(RechargeMethod $rechargeMethod): RedirectResponse
    {
        $rechargeMethod->delete();

        return back()->with('status', __('admin.methods.deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bank,crypto'],
            'wallet_address' => ['nullable', 'string', 'max:255'],
            'network' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function configFrom(array $data): array
    {
        return array_filter([
            'wallet_address' => $data['wallet_address'] ?? null,
            'network' => $data['network'] ?? null,
            'internal_note' => $data['internal_note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
