<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeRequest;
use App\Services\Admin\WalletApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RechargeRequestController extends Controller
{
    public function __construct(private readonly WalletApprovalService $walletApproval) {}

    public function index(Request $request): View
    {
        $requests = RechargeRequest::query()
            ->with(['user', 'rechargeMethod'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.recharge-requests.index', compact('requests'));
    }

    public function approve(RechargeRequest $rechargeRequest): RedirectResponse
    {
        $this->walletApproval->approveRecharge($rechargeRequest);

        return back()->with('status', __('admin.requests.approved'));
    }

    public function reject(Request $request, RechargeRequest $rechargeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->walletApproval->rejectRecharge($rechargeRequest, $validated['admin_note'] ?? null);

        return back()->with('status', __('admin.requests.rejected'));
    }
}
