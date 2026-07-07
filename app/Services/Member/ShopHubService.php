<?php

namespace App\Services\Member;

use App\Models\ProductReview;
use App\Models\User;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShopHubService
{
    public function __construct(private readonly ShopDashboardService $shopDashboard) {}

    public function ensureSeller(User $user): void
    {
        abort_unless($user->isShop(), 403);
    }

    /** @return array<string, mixed> */
    public function dashboard(User $user): array
    {
        $this->ensureSeller($user);
        $user->loadMissing(['shop', 'wallet']);

        $stats = $this->shopDashboard->statsFor($user);
        $statusCounts = $this->statusCounts($user);

        return [
            'shop' => $this->shopPayload($user),
            'stats' => $stats,
            'order_status_counts' => $statusCounts,
            'quick_links' => $this->quickLinks(),
            'seller_stats' => [
                'completed_orders' => $stats['completed_orders'],
                'failed_orders' => $stats['failed_orders'],
                'order_reviews' => $stats['order_reviews'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function rank(User $user): array
    {
        $this->ensureSeller($user);
        $user->loadMissing('shop');
        $stats = $this->shopDashboard->statsFor($user);
        $shop = $user->shop;

        return [
            'merchant_level' => $shop?->merchantLevel() ?? 'L1',
            'loyalty_points' => $shop?->loyaltyPoints((int) $stats['completed_orders']) ?? 0,
            'credit_score' => $stats['credit_score'],
            'star_rating' => $stats['star_rating'],
            'followers' => $stats['followers'],
            'seller_type' => $shop?->isBusiness() ? 'business' : ($shop ? 'personal' : null),
        ];
    }

    /** @return array<string, mixed> */
    public function info(User $user): array
    {
        $this->ensureSeller($user);
        abort_unless($user->shop, 403);

        $shop = $user->shop;

        return [
            'name' => $shop->name,
            'description' => $shop->description,
            'keywords' => $shop->keywords,
            'address' => $shop->address,
            'country' => $shop->country,
            'logo' => $shop->displayLogoUrl(),
            'industry_id' => $shop->industry_id,
            'industry_label' => $shop->industryLabel(),
            'industry_rate' => $shop->industryRate(),
            'business_categories' => $shop->businessCategoryLabels(),
            'contact_name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    /** @return array<string, list<array<string, string>>> */
    public function menu(User $user): array
    {
        $this->ensureSeller($user);
        $user->loadMissing('shop');

        $shopInfo = $user->shop
            ? route('member.shop-hub.info')
            : route('member.shop-application.create');

        $paymentPassword = $user->hasPaymentPassword()
            ? route('member.payment-password.edit')
            : route('member.payment-password.create');

        return [
            'shop' => [
                ['key' => 'basic_info', 'label' => __('member.shop_hub.basic_info'), 'url' => $shopInfo],
                ['key' => 'business_detail', 'label' => __('member.shop_hub.business_detail'), 'url' => route('member.financial-report.index')],
                ['key' => 'merchant_rank', 'label' => __('member.shop_hub.merchant_rank'), 'url' => route('member.shop-hub.rank')],
                ['key' => 'shop_entry', 'label' => __('member.shop_hub.shop_entry'), 'url' => $shopInfo],
                ['key' => 'return_address', 'label' => __('member.shop_hub.return_address'), 'url' => route('member.shipping.index')],
                ['key' => 'service_info', 'label' => __('member.shop_hub.service_info'), 'url' => route('member.chat.index')],
                ['key' => 'account_password', 'label' => __('member.shop_hub.account_password'), 'url' => route('member.profile.password.edit')],
                ['key' => 'withdraw_password', 'label' => __('member.shop_hub.withdraw_password'), 'url' => $paymentPassword],
                ['key' => 'sub_accounts', 'label' => __('member.shop_hub.sub_accounts'), 'url' => route('member.shop-hub.sub-accounts.index')],
            ],
            'account' => [
                ['key' => 'account_detail', 'label' => __('member.shop_hub.account_detail'), 'url' => route('member.wallet.hub')],
                ['key' => 'withdraw', 'label' => __('member.shop_hub.withdraw'), 'url' => route('member.wallet.withdrawal')],
                ['key' => 'withdraw_records', 'label' => __('member.shop_hub.withdraw_records'), 'url' => route('member.wallet.withdrawal-records')],
                ['key' => 'recharge', 'label' => __('member.shop_hub.recharge'), 'url' => route('member.wallet.recharge')],
                ['key' => 'recharge_records', 'label' => __('member.shop_hub.recharge_records'), 'url' => route('member.wallet.fund-records')],
                ['key' => 'payout_account', 'label' => __('member.shop_hub.payout_account'), 'url' => route('member.payout-accounts.index')],
                ['key' => 'balance', 'label' => __('member.shop_hub.balance'), 'url' => route('member.wallet.hub')],
            ],
            'goods' => [
                ['key' => 'product_manage', 'label' => __('member.shop_hub.product_manage'), 'url' => route('member.products.manage.index')],
                ['key' => 'distribution', 'label' => __('member.products.distribution_center'), 'url' => route('member.products.distributions.index')],
                ['key' => 'financial_report', 'label' => __('member.my.financial_report'), 'url' => route('member.financial-report.index')],
                ['key' => 'orders', 'label' => __('member.my.order_management'), 'url' => route('member.seller.orders.index')],
                ['key' => 'refunds', 'label' => __('member.shop_hub.refunds'), 'url' => route('member.seller.refunds.index')],
            ],
        ];
    }

    public function reviews(User $user): LengthAwarePaginator
    {
        $this->ensureSeller($user);

        return ProductReview::query()
            ->published()
            ->with(['user', 'product'])
            ->when(
                $user->shop,
                fn ($query) => $query->whereHas('product', fn ($product) => $product->where('shop_id', $user->shop->id)),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->latest()
            ->paginate(15);
    }

    public function statusCounts(User $user): Collection
    {
        return $user->shop
            ? ShopOrderStatusBadges::unseenCounts($user->shop, $user->id)
            : ShopOrderStatusBadges::sellerStatusCounts($user->id);
    }

    /** @return list<array<string, string>> */
    private function quickLinks(): array
    {
        return [
            ['key' => 'distribute', 'label' => __('member.shop_hub.distribute'), 'url' => route('member.products.distributions.index')],
            ['key' => 'recharge', 'label' => __('member.shop_hub.recharge'), 'url' => route('member.wallet.recharge')],
            ['key' => 'support', 'label' => __('member.shop_hub.support'), 'url' => route('member.chat.index')],
            ['key' => 'all_menu', 'label' => __('member.shop_hub.all_menu'), 'url' => route('member.shop-hub.menu')],
        ];
    }

    /** @return array<string, mixed>|null */
    private function shopPayload(User $user): ?array
    {
        if (! $user->shop) {
            return null;
        }

        $shop = $user->shop;
        $stats = $this->shopDashboard->statsFor($user);

        return [
            'id' => $shop->id,
            'name' => $shop->name,
            'logo' => $shop->displayLogoUrl(),
            'merchant_level' => $shop->merchantLevel(),
            'loyalty_points' => $shop->loyaltyPoints((int) $stats['completed_orders']),
        ];
    }
}
