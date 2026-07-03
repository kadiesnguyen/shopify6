<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberComplaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $complaints = MemberComplaint::query()
            ->with('user:id,email,phone,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.complaints.index', compact('complaints'));
    }

    public function resolve(MemberComplaint $complaint): RedirectResponse
    {
        $complaint->update(['status' => MemberComplaint::STATUS_RESOLVED]);

        return back()->with('status', __('admin.complaints.resolved_status'));
    }

    public function destroy(MemberComplaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return back()->with('status', __('admin.complaints.deleted'));
    }
}
