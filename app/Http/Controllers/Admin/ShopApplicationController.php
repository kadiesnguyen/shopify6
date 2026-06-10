<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopApplication;
use App\Services\Admin\ShopApplicationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopApplicationController extends Controller
{
    public function __construct(private readonly ShopApplicationApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $applications = ShopApplication::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('shop_name', 'like', $term)
                        ->orWhere('real_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term)->orWhere('phone', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shop-applications.index', compact('applications'));
    }

    public function approve(ShopApplication $shopApplication): RedirectResponse
    {
        $this->approvalService->approve($shopApplication, auth()->user());

        return back()->with('status', __('admin.shop_applications.approved'));
    }

    public function reject(Request $request, ShopApplication $shopApplication): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->approvalService->reject($shopApplication, auth()->user(), $request->string('admin_note')->toString() ?: null);

        return back()->with('status', __('admin.shop_applications.rejected'));
    }
}
