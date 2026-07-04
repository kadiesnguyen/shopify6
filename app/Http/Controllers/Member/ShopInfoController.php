<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ShopInfoRequest;
use App\Support\ShopIndustryRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopInfoController extends Controller
{
    public function __construct(private readonly ShopIndustryRegistry $industries) {}

    public function edit(): View
    {
        $user = auth()->user()->load('shop');
        abort_unless($user->isShop() && $user->shop, 403);

        $shop = $user->shop;
        $industry = filled($shop->industry_id) ? $this->industries->industry((string) $shop->industry_id) : null;

        return view('member.shop-hub.info', compact('user', 'shop', 'industry'));
    }

    public function update(ShopInfoRequest $request): RedirectResponse
    {
        $user = auth()->user()->load('shop');
        $shop = $user->shop;
        abort_unless($shop, 403);

        $data = $request->safe()->only(['name', 'description', 'keywords', 'address']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('shops/logos', 'public');
        }

        $shop->update($data);
        $user->update([
            'name' => $request->validated('contact_name'),
            'phone' => $request->validated('phone'),
        ]);

        return redirect()
            ->route('member.shop-hub.info')
            ->with('status', __('member.shop_hub.info_saved'));
    }
}
