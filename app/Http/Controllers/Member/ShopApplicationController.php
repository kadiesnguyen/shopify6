<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ShopApplicationRequest;
use App\Models\ShopApplication;
use App\Support\ShopIndustryRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopApplicationController extends Controller
{
    public function __construct(private readonly ShopIndustryRegistry $industries) {}

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();
        $shop = $user->shop;

        if ($user->isShop() && $shop?->isBusiness()) {
            return redirect()
                ->route('member.home')
                ->with('status', __('member.shop_application.already_business_shop'));
        }

        $pending = ShopApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ShopApplication::STATUS_PENDING)
            ->latest()
            ->first();

        if ($pending) {
            return redirect()
                ->route('member.my.index')
                ->with('status', __('member.shop_application.pending_exists'));
        }

        $mode = $this->applicationModeFor($user, $shop);

        return view('member.shop-application.create', [
            'mode' => $mode,
            'shop' => $shop,
            'defaultSellerType' => $mode === ShopApplication::KIND_UPGRADE
                ? ShopApplication::TYPE_BUSINESS
                : ShopApplication::TYPE_PERSONAL,
            'industries' => $this->industries->industries(),
        ]);
    }

    public function store(ShopApplicationRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $shop = $user->shop;

        if ($user->isShop() && $shop?->isBusiness()) {
            return redirect()
                ->route('member.home')
                ->with('status', __('member.shop_application.already_business_shop'));
        }

        $hasPending = ShopApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ShopApplication::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return redirect()
                ->route('member.my.index')
                ->with('status', __('member.shop_application.pending_exists'));
        }

        $mode = $this->applicationModeFor($user, $shop);
        $sellerType = $request->validated('seller_type');

        if ($mode === ShopApplication::KIND_UPGRADE && $sellerType !== ShopApplication::TYPE_BUSINESS) {
            return back()
                ->withInput()
                ->withErrors(['seller_type' => __('member.shop_application.upgrade_business_only')]);
        }

        $data = collect($request->validated())->except(['logo', 'id_front', 'id_back', 'terms'])->all();
        $data['business_category_ids'] = array_values(array_map('intval', $data['business_category_ids'] ?? []));

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('shop-applications/logos', 'public');
        }

        $data['id_front'] = $request->file('id_front')->store('shop-applications/id', 'public');
        $data['id_back'] = $request->file('id_back')->store('shop-applications/id', 'public');
        $data['user_id'] = $user->id;
        $data['application_kind'] = $mode;
        $data['status'] = ShopApplication::STATUS_PENDING;

        ShopApplication::query()->create($data);

        return redirect()
            ->route('member.my.index')
            ->with('status', $mode === ShopApplication::KIND_UPGRADE
                ? __('member.shop_application.upgrade_submitted')
                : __('member.shop_application.submitted'));
    }

    private function applicationModeFor(\App\Models\User $user, ?\App\Models\Shop $shop): string
    {
        if ($user->isShop() && $shop?->isPersonal()) {
            return ShopApplication::KIND_UPGRADE;
        }

        return ShopApplication::KIND_REGISTRATION;
    }
}
