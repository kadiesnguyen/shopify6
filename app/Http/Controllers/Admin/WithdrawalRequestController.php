<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\Admin\WalletApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalRequestController extends Controller
{
    public function __construct(private readonly WalletApprovalService $walletApproval) {}

    public function index(Request $request): View
    {
        $requests = WithdrawalRequest::query()
            ->with(['user', 'withdrawalMethod'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.withdrawal-requests.index', compact('requests'));
    }

    public function approve(WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $this->walletApproval->approveWithdrawal($withdrawalRequest);

        return back()->with('status', __('admin.requests.approved'));
    }

    public function reject(Request $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->walletApproval->rejectWithdrawal(
            $withdrawalRequest,
            $validated['admin_note'] ?? null,
        );

        return back()->with('status', __('admin.requests.rejected'));
    }
}
