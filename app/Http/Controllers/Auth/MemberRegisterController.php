<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MemberRegisterRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Auth\MemberCredentials;
use App\Support\Auth\PersistentLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MemberRegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.member-register');
    }

    public function store(MemberRegisterRequest $request): RedirectResponse
    {
        Role::firstOrCreate(['name' => 'member']);

        $profile = MemberCredentials::fromLogin($request->validated('login'));

        $user = User::query()->create([
            'username' => $profile['username'],
            'user_code' => User::allocateNextUserCode(),
            'name' => $profile['name'],
            'email' => $profile['email'],
            'phone' => $profile['phone'],
            'password' => Hash::make($request->validated('password')),
            'status' => 'active',
        ]);

        $user->assignRole('member');

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        PersistentLogin::configureRememberDuration();
        Auth::login($user, true);
        $request->session()->regenerate();

        PersistentLogin::finalize($request, $user);

        return redirect()->route('member.home');
    }
}
