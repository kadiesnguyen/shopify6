<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberComplaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(): View
    {
        $complaints = MemberComplaint::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('member.complaints.index', compact('complaints'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        MemberComplaint::query()->create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'status' => MemberComplaint::STATUS_PENDING,
        ]);

        return back()->with('status', __('member.complaints.submitted'));
    }
}
