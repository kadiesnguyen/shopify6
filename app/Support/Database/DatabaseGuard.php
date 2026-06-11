<?php

namespace App\Support\Database;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\RechargeRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalRequest;

class DatabaseGuard
{
    public static function hasPreservedData(): bool
    {
        return self::counts()['total'] > 0;
    }

    public static function hasCommerceData(): bool
    {
        $counts = self::counts();

        return $counts['products'] > 0
            || $counts['orders'] > 0
            || $counts['transactions'] > 0
            || $counts['recharge_requests'] > 0
            || $counts['withdrawal_requests'] > 0
            || $counts['product_distributions'] > 0;
    }

    public static function hasSieummoCatalog(): bool
    {
        return self::counts()['sieummo_products'] > 0;
    }

    /** @return array<string, int> */
    public static function counts(): array
    {
        $users = self::safeCount(User::class);
        $products = self::safeCount(Product::class);
        $sieummoProducts = self::safeCount(Product::class, fn ($q) => $q->where('slug', 'like', 'sm-%'));
        $orders = self::safeCount(Order::class);
        $transactions = self::safeCount(Transaction::class);
        $rechargeRequests = self::safeCount(RechargeRequest::class);
        $withdrawalRequests = self::safeCount(WithdrawalRequest::class);
        $distributions = self::safeCount(ProductDistribution::class);

        return [
            'users' => $users,
            'products' => $products,
            'sieummo_products' => $sieummoProducts,
            'orders' => $orders,
            'transactions' => $transactions,
            'recharge_requests' => $rechargeRequests,
            'withdrawal_requests' => $withdrawalRequests,
            'product_distributions' => $distributions,
            'total' => $users + $products + $orders + $transactions
                + $rechargeRequests + $withdrawalRequests + $distributions,
        ];
    }

    /** @return list<string> */
    public static function summaryLines(): array
    {
        $counts = self::counts();

        return [
            "users={$counts['users']}",
            "products={$counts['products']} (sm-*={$counts['sieummo_products']})",
            "orders={$counts['orders']}",
            "transactions={$counts['transactions']}",
            "recharge={$counts['recharge_requests']}",
            "withdrawal={$counts['withdrawal_requests']}",
            "distributions={$counts['product_distributions']}",
        ];
    }

    /** @param  class-string  $model */
    private static function safeCount(string $model, ?callable $constraint = null): int
    {
        try {
            $query = $model::query();

            if ($constraint) {
                $constraint($query);
            }

            return $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
