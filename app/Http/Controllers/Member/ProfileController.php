<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberLoginPasswordRequest;
use App\Http\Requests\Member\MemberProfileAvatarRequest;
use App\Http\Requests\Member\MemberProfileEmailRequest;
use App\Http\Requests\Member\MemberProfileNameRequest;
use App\Http\Requests\Member\MemberProfilePhoneRequest;
use App\Http\Requests\Member\MemberProfileUpdateRequest;
use App\Support\Storage\PublicUploadStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('member.profile.show', [
            'user' => auth()->user(),
        ]);
    }

    public function update(MemberProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'gender', 'birthday']);

        if ($request->hasFile('avatar')) {
            $directory = 'avatars/'.$user->id;

            PublicUploadStorage::delete($user->avatar);

            $avatarPath = PublicUploadStorage::store($request->file('avatar'), $directory);
            $data['avatar'] = $avatarPath;

            if ($user->isShop() && $user->shop) {
                PublicUploadStorage::delete($user->shop->logo);
                $user->shop->update(['logo' => $avatarPath]);
            }
        }

        $user->update($data);

        return redirect()
            ->route('member.profile.show')
            ->with('status', __('member.profile.updated'));
    }

    public function updateAvatar(MemberProfileAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $file = $request->file('avatar');
        $directory = 'avatars/'.$user->id;

        PublicUploadStorage::delete($user->avatar);

        $avatarPath = PublicUploadStorage::store($file, $directory);

        $user->update(['avatar' => $avatarPath]);

        if ($user->isShop() && $user->shop) {
            PublicUploadStorage::delete($user->shop->logo);
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
            ->route('member.settings.change-account')
            ->with('status', __('member.profile.phone_updated'));
    }

    public function updateEmail(MemberProfileEmailRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'email' => strtolower(trim($request->validated('email'))),
            'email_verified_at' => null,
        ])->save();

        return redirect()
            ->route('member.settings.change-account')
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
            ->route('member.settings.index')
            ->with('status', __('member.profile.login_password_updated'));
    }
}
