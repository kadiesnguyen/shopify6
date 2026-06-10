<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProfilePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('admin.profile.show', [
            'user' => auth()->user(),
        ]);
    }

    public function updatePassword(AdminProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()
            ->route('admin.profile.show')
            ->with('status', __('admin.profile.password_updated'));
    }
}
