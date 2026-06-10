<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $methods = PaymentMethod::query()->orderBy('sort_order')->paginate(15);

        $modalMethod = null;
        $showMethodModal = $request->boolean('show_create') || $request->filled('edit');

        if ($request->filled('edit')) {
            $modalMethod = PaymentMethod::query()->find($request->integer('edit'));
        }

        return view('admin.payment-methods.index', compact('methods', 'modalMethod', 'showMethodModal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        PaymentMethod::query()->create([
            'name' => $data['name'],
            'code' => $this->uniqueCode($data['name']),
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => $this->configFrom($data),
        ]);

        return redirect()->route('admin.payment-methods.index')->with('status', __('admin.methods.saved'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $data = $this->validated($request);

        $paymentMethod->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'config' => array_merge($paymentMethod->config ?? [], $this->configFrom($data)),
        ]);

        return redirect()->route('admin.payment-methods.index')->with('status', __('admin.methods.updated'));
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        return back()->with('status', __('admin.methods.deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function configFrom(array $data): array
    {
        return array_filter([
            'subtitle' => $data['subtitle'] ?? null,
            'icon' => $data['icon'] ?? null,
            'internal_note' => $data['internal_note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name) ?: 'method';
        $code = $base;
        $suffix = 1;

        while (PaymentMethod::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
