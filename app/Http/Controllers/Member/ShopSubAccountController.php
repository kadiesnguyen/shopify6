<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ShopSubAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopSubAccountController extends Controller
{
    public function index(): View
    {
        $shop = $this->shopOrAbort();

        $accounts = ShopSubAccount::query()
            ->where('shop_id', $shop->id)
            ->latest()
            ->get();

        return view('member.shop-hub.sub-accounts.index', compact('accounts', 'shop'));
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $this->shopOrAbort();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', Rule::unique('shop_sub_accounts', 'username')->where('shop_id', $shop->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        ShopSubAccount::query()->create([
            ...$data,
            'shop_id' => $shop->id,
            'status' => ShopSubAccount::STATUS_ACTIVE,
        ]);

        return back()->with('status', __('member.shop_hub.sub_account_created'));
    }

    public function destroy(ShopSubAccount $subAccount): RedirectResponse
    {
        $shop = $this->shopOrAbort();
        abort_unless($subAccount->shop_id === $shop->id, 403);
        $subAccount->delete();

        return back()->with('status', __('member.shop_hub.sub_account_deleted'));
    }

    private function shopOrAbort(): \App\Models\Shop
    {
        abort_unless(auth()->user()->isShop(), 403);
        $shop = auth()->user()->shop;
        abort_unless($shop, 403);

        return $shop;
    }
}
