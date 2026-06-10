<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminUserActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserActionController extends Controller
{
    public function __construct(private readonly AdminUserActionService $actions) {}

    public function updateBalance(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'balance_pending' => ['required', 'numeric', 'min:0'],
            'balance' => ['required', 'numeric', 'min:0'],
            'balance_frozen' => ['required', 'numeric', 'min:0'],
        ]);

        $this->actions->updateBalance($user, $validated);

        return $this->redirectBack(__('admin.users.actions.balance_updated'));
    }

    public function deposit(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->actions->deposit($user, (float) $validated['amount'], $validated['note'] ?? null);

        return $this->redirectBack(__('admin.users.actions.deposited'));
    }

    public function changePassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $this->actions->changePassword($user, $validated['password']);

        return $this->redirectBack(__('admin.users.actions.password_updated'));
    }

    public function changePaymentPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $validated = $request->validate([
            'payment_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $this->actions->changePaymentPassword($user, $validated['payment_password']);

        return $this->redirectBack(__('admin.users.actions.payment_password_updated'));
    }

    public function toggleAccountLock(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return $this->redirectBack()->withErrors(['user' => __('admin.users.actions.cannot_lock_self')]);
        }

        $updated = $this->actions->toggleAccountLock($user);

        $message = $updated->status === User::STATUS_BANNED
            ? __('admin.users.actions.account_locked')
            : __('admin.users.actions.account_unlocked');

        return $this->redirectBack($message);
    }

    public function toggleDistributionLock(User $user): RedirectResponse
    {
        $updated = $this->actions->toggleDistributionLock($user);

        $message = $updated->distribution_locked
            ? __('admin.users.actions.distribution_locked')
            : __('admin.users.actions.distribution_unlocked');

        return $this->redirectBack($message);
    }

    private function redirectBack(?string $status = null): RedirectResponse
    {
        $redirect = redirect()->route('admin.users.index', request()->only([
            'q', 'role', 'shop_application',
        ]));

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
