<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberLoginPasswordRequest;
use App\Http\Requests\Member\MemberProfileAvatarRequest;
use App\Http\Requests\Member\MemberProfileEmailRequest;
use App\Http\Requests\Member\MemberProfileNameRequest;
use App\Http\Requests\Member\MemberProfilePhoneRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('member.profile.show', [
            'user' => auth()->user(),
        ]);
    }

    public function updateAvatar(MemberProfileAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $file = $request->file('avatar');
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $relativeDir = 'uploads/avatars/'.$user->id;
        $absoluteDir = public_path($relativeDir);

        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $this->deleteAvatarFile($user->avatar);

        $file->move($absoluteDir, $filename);

        $avatarPath = $relativeDir.'/'.$filename;

        $user->update(['avatar' => $avatarPath]);

        if ($user->shop) {
            $this->deleteAvatarFile($user->shop->logo);
            $user->shop->update(['logo' => $avatarPath]);
        }

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.avatar_updated'));
    }

    public function updateName(MemberProfileNameRequest $request): RedirectResponse
    {
        $request->user()->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.name_updated'));
    }

    public function updatePhone(MemberProfilePhoneRequest $request): RedirectResponse
    {
        $request->user()->update([
            'phone' => trim($request->validated('phone')),
        ]);

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.phone_updated'));
    }

    public function updateEmail(MemberProfileEmailRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'email' => strtolower(trim($request->validated('email'))),
            'email_verified_at' => null,
        ])->save();

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.email_updated_pending'));
    }

    public function editPassword(): View
    {
        return view('member.profile.login-password');
    }

    public function updatePassword(MemberLoginPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.login_password_updated'));
    }

    private function deleteAvatarFile(?string $avatar): void
    {
        if (! filled($avatar) || str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return;
        }

        if (str_starts_with($avatar, 'uploads/')) {
            $path = public_path($avatar);
            if (is_file($path)) {
                unlink($path);
            }

            return;
        }

        Storage::disk('public')->delete($avatar);
    }
}
