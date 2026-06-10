<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Notification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('member.notifications.index', compact('notifications'));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['read_at' => now()]);

        return back()->with('status', __('member.notifications.mark_read'));
    }
}
