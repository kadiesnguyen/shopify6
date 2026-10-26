<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\UserPayoutAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayoutAccountController extends Controller
{
    public function index(): View
    {
        $accounts = auth()->user()->payoutAccounts()->latest()->get();

        return view('member.payout-accounts.index', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([UserPayoutAccount::TYPE_BANK, UserPayoutAccount::TYPE_CRYPTO])],
            'label' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'crypto_currency' => ['nullable', 'string', 'max:20'],
            'crypto_network' => ['nullable', 'string', 'max:120'],
            'crypto_address' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($data['type'] === UserPayoutAccount::TYPE_BANK) {
            abort_unless(filled($data['bank_name'] ?? null) && filled($data['account_name'] ?? null) && filled($data['account_number'] ?? null), 422);
        } else {
            abort_unless(filled($data['crypto_currency'] ?? null) && filled($data['crypto_network'] ?? null) && filled($data['crypto_address'] ?? null), 422);
        }

        $user = auth()->user();

        if ($request->boolean('is_default')) {
            $user->payoutAccounts()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $user->payoutAccounts()->create($data);

        return back()->with('status', __('member.payout_accounts.saved'));
    }

    public function destroy(UserPayoutAccount $payoutAccount): RedirectResponse
    {
        abort_unless($payoutAccount->user_id === auth()->id(), 403);
        $payoutAccount->delete();

        return back()->with('status', __('member.payout_accounts.deleted'));
    }
}
