<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordChangeRequestController extends Controller
{
    public function index(): View
    {
        $requests = PasswordChangeRequest::query()
            ->with('user')
            ->latest()
            ->paginate(15);

        return view('admin.password-change-requests.index', compact('requests'));
    }

    public function approve(PasswordChangeRequest $passwordChangeRequest): RedirectResponse
    {
        $passwordChangeRequest->update([
            'status' => PasswordChangeRequest::STATUS_APPROVED,
            'processed_at' => now(),
        ]);

        return back()->with('status', __('admin.requests.approved'));
    }

    public function reject(PasswordChangeRequest $passwordChangeRequest): RedirectResponse
    {
        $passwordChangeRequest->update([
            'status' => PasswordChangeRequest::STATUS_REJECTED,
            'processed_at' => now(),
        ]);

        return back()->with('status', __('admin.requests.rejected'));
    }
}
