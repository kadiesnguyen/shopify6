<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Promotion;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $promotions = Promotion::query()
            ->with(['user', 'shop'])
            ->when($request->string('q'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->filled('days'), function ($q) use ($request): void {
                $q->where('created_at', '>=', now()->subDays((int) $request->input('days')));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => new Promotion,
            'users' => User::query()->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        Promotion::query()->create($request->validated());

        return redirect()->route('admin.promotions.index')->with('status', __('admin.promotions.created'));
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'users' => User::query()->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
        ]);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated());

        return redirect()->route('admin.promotions.index')->with('status', __('admin.promotions.updated'));
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('status', __('admin.promotions.deleted'));
    }
}
