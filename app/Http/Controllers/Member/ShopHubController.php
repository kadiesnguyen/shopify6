<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\Member\ShopDashboardService;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\View\View;

class ShopHubController extends Controller
{
    public function __construct(private readonly ShopDashboardService $shopDashboard) {}

    public function index(): View
    {
        return $this->render('member.shop-hub.index');
    }

    public function menu(): View
    {
        return $this->render('member.shop-hub.menu');
    }

    public function rank(): View
    {
        $user = auth()->user()->load('shop');
        abort_unless($user->isShop(), 403);

        $shop = $user->shop;
        $stats = $this->shopDashboard->statsFor($user);

        return view('member.shop-hub.rank', compact('user', 'shop', 'stats'));
    }

    public function reviews(): View
    {
        $user = auth()->user()->load('shop');
        abort_unless($user->isShop(), 403);

        $reviews = ProductReview::query()
            ->published()
            ->with(['user', 'product'])
            ->when(
                $user->shop,
                fn ($query) => $query->whereHas('product', fn ($product) => $product->where('shop_id', $user->shop->id)),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->latest()
            ->paginate(15);

        return view('member.shop-hub.reviews', compact('user', 'reviews'));
    }

    private function render(string $view): View
    {
        $user = auth()->user()->load(['shop', 'wallet']);
        abort_unless($user->isShop(), 403);

        $stats = $this->shopDashboard->statsFor($user);
        $statusCounts = $user->shop
            ? ShopOrderStatusBadges::unseenCounts($user->shop, $user->id)
            : ShopOrderStatusBadges::sellerStatusCounts($user->id);

        return view($view, compact('user', 'stats', 'statusCounts'));
    }
}
