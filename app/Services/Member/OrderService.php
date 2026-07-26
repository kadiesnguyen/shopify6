<?php

namespace App\Services\Member;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly ProductDistributionService $distributionService,
        private readonly MemberNotificationService $notifications,
    ) {}

    public function placeOrder(User $user, Product $product, int $qty = 1, ?int $displayShopId = null): Order
    {
        return DB::transaction(function () use ($user, $product, $qty, $displayShopId): Order {
            $product = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($product->status === Product::STATUS_ACTIVE, 404);

            $distribution = $this->distributionService->resolveForOrder($product, $displayShopId, $user->id);

            if (! $distribution) {
                throw new RuntimeException(
                    $this->distributionService->hasAvailableDistributionForSeller($product, $user->id)
                        ? 'cannot_buy_own_shop'
                        : 'product_not_distributed',
                );
            }

            $qty = max(1, min($qty, $product->stock));

            if ($product->stock < $qty) {
                throw new RuntimeException('insufficient_stock');
            }

            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $unitPrice = (float) $distribution->selling_price;
            $subtotal = $unitPrice * $qty;
        $purchaseCost = (float) $distribution->purchase_price * $qty;
        $commission = $this->distributionService->profitForQuantity($distribution, $qty);

            if (! $wallet || (float) $wallet->balance < $subtotal) {
                throw new RuntimeException('insufficient_balance');
            }

            $seller = User::query()->with('shop')->find($distribution->user_id);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'shop_id' => $seller?->shop?->id ?? $product->shop_id,
                'seller_id' => $distribution->user_id,
                'product_distribution_id' => $distribution->id,
                'order_no' => 'ORD-'.strtoupper(Str::random(10)),
                'total' => $subtotal,
                'commission' => $commission,
                'purchase_cost' => $purchaseCost,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_method' => 'wallet',
                'paid_at' => now(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->image,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'purchase_price' => $distribution->purchase_price,
                'commission' => $distribution->commission,
                'subtotal' => $subtotal,
            ]);

            $wallet->decrement('balance', $subtotal);
            $product->decrement('stock', $qty);

            Transaction::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $subtotal,
                'type' => Transaction::TYPE_ORDER_PAYMENT,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => $order->order_no,
                'description' => 'Order payment '.$order->order_no,
                'processed_at' => now(),
            ]);

            // ponytail: no reserve — listing stays buyable after sale (demo resale).
            // Ceiling: same distribution can fulfill concurrent orders.

            $this->notifications->notifyOrderNeedsPayment($order);

            return $order;
        });
    }
}
