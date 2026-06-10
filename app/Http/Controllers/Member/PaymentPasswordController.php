<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\PaymentPasswordRequest;
use App\Support\Auth\AuthenticatedUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = AuthenticatedUserResolver::fromSession();

        if (! $user) {
            return redirect()->route('auth.login')->withErrors([
                'login' => __('auth.session_stale'),
            ]);
        }

        if ($user->hasPaymentPassword()) {
            $redirect = $request->string('redirect')->toString();

            return redirect($redirect !== '' ? $redirect : route('member.profile.show'));
        }

        return view('member.payment-password.create', [
            'redirect' => $request->string('redirect')->toString(),
        ]);
    }

    public function store(PaymentPasswordRequest $request): RedirectResponse
    {
        $user = AuthenticatedUserResolver::fromSession();

        if (! $user) {
            return redirect()->route('auth.login')->withErrors([
                'login' => __('auth.session_stale'),
            ]);
        }

        if ($user->hasPaymentPassword()) {
            return redirect()->route('member.profile.show');
        }

        $user->update([
            'payment_password' => $request->validated('payment_password'),
        ]);

        $user->refresh();

        if (! $user->hasPaymentPassword()) {
            return back()
                ->withInput()
                ->withErrors(['payment_password' => __('member.payment_password.save_failed')]);
        }

        $redirect = $request->string('redirect')->toString();

        return redirect($redirect !== '' ? $redirect : route('member.home'))
            ->with('status', __('member.payment_password.saved'));
    }
}
