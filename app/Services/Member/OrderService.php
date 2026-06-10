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
        private readonly OrderSettlementService $settlement,
    ) {}

    public function placeOrder(User $user, Product $product, int $qty = 1): Order
    {
        return DB::transaction(function () use ($user, $product, $qty): Order {
            $product = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($product->status === Product::STATUS_ACTIVE, 404);

            $qty = max(1, min($qty, $product->stock));

            if ($product->stock < $qty) {
                throw new RuntimeException('insufficient_stock');
            }

            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $subtotal = (float) $product->selling_price * $qty;
            $commission = (float) $product->commission * $qty;
            $purchaseCost = (float) $product->purchase_price * $qty;

            if (! $wallet || (float) $wallet->balance < $subtotal) {
                throw new RuntimeException('insufficient_balance');
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'shop_id' => $product->shop_id,
                'seller_id' => $product->user_id,
                'order_no' => 'ORD-'.strtoupper(Str::random(10)),
                'total' => $subtotal,
                'commission' => $commission,
                'purchase_cost' => $purchaseCost,
                'status' => Order::STATUS_AWAITING_PICKUP,
                'payment_method' => 'wallet',
                'paid_at' => now(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->image,
                'qty' => $qty,
                'unit_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'commission' => $product->commission,
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

            $this->settlement->chargeSellerProductCost($order, $purchaseCost);

            return $order;
        });
    }
}
