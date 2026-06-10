<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Member\ShopDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopDashboardController extends Controller
{
    public function __construct(private readonly ShopDashboardService $dashboard) {}

    public function index(): View|RedirectResponse
    {
        $user = auth()->user()->load('shop');

        if (! $user->shop) {
            return redirect()
                ->route('member.shop-application.create')
                ->with('status', __('member.shop_dashboard.no_shop'));
        }

        return view('member.shop-dashboard.index', [
            'shop' => $user->shop,
            'stats' => $this->dashboard->statsFor($user),
        ]);
    }
}
