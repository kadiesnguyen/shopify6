<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Member\ShopDashboardService;
use Illuminate\View\View;

class ShopHubController extends Controller
{
    public function __construct(private readonly ShopDashboardService $shopDashboard) {}

    public function index(): View
    {
        $user = auth()->user()->load('shop');
        // Shop role is enough: sellers without a shop row still get order-based stats.
        abort_unless($user->isShop(), 403);

        $stats = $this->shopDashboard->statsFor($user);

        return view('member.shop-hub.index', compact('user', 'stats'));
    }
}
