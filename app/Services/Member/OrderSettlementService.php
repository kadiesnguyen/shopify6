<?php

namespace App\Services\Member;

use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderSettlementService
{
    public function applyStatusChange(Order $order, string $previousStatus, string $newStatus): Order
    {
        if ($previousStatus === $newStatus) {
            return $order;
        }

        return DB::transaction(function () use ($order, $previousStatus, $newStatus): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($newStatus === Order::STATUS_COMPLETED) {
                $this->creditSellerCommission($order);
                $order->completed_at = now();
            }

            if ($newStatus === Order::STATUS_CANCELLED) {
                $this->handleCancellation($order, $previousStatus);
            }

            $order->status = $newStatus;
            $order->save();

            return $order->fresh(['items', 'buyer.wallet', 'seller.wallet']);
        });
    }

    public function chargeSellerProductCost(Order $order, float $purchaseCost): void
    {
        if ($purchaseCost <= 0 || ! $order->seller_id) {
            return;
        }

        $reference = $order->order_no.'-seller-cost';

        if ($this->transactionExists($reference)) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $order->seller_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet || (float) $wallet->balance < $purchaseCost) {
            throw new RuntimeException('insufficient_seller_balance');
        }

        $wallet->decrement('balance', $purchaseCost);

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $purchaseCost,
            'type' => Transaction::TYPE_PRODUCT_COST,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order product cost '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    public function creditSellerCommission(Order $order): void
    {
        $commission = (float) $order->commission;

        if ($commission <= 0 || ! $order->seller_id) {
            return;
        }

        $reference = $order->order_no.'-seller-commission';

        if ($this->transactionExists($reference)) {
            return;
        }

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $order->seller_id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $wallet->increment('balance', $commission);

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $commission,
            'type' => Transaction::TYPE_COMMISSION,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order commission '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    private function handleCancellation(Order $order, string $previousStatus): void
    {
        if ($previousStatus === Order::STATUS_COMPLETED) {
            $this->reverseSellerCommission($order);
        }

        $this->refundSellerProductCost($order);
        $this->refundBuyerPayment($order);
        $this->restoreStock($order);
    }

    private function refundSellerProductCost(Order $order): void
    {
        $amount = (float) $order->purchase_cost;

        if ($amount <= 0 || ! $order->seller_id) {
            return;
        }

        if (! $this->transactionExists($order->order_no.'-seller-cost')) {
            return;
        }

        $reference = $order->order_no.'-seller-cost-refund';

        if ($this->transactionExists($reference)) {
            return;
        }

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $order->seller_id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $wallet->increment('balance', $amount);

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_REFUND,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order product cost refund '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    private function reverseSellerCommission(Order $order): void
    {
        $commission = (float) $order->commission;

        if ($commission <= 0 || ! $order->seller_id) {
            return;
        }

        if (! $this->transactionExists($order->order_no.'-seller-commission')) {
            return;
        }

        $reference = $order->order_no.'-seller-commission-reversal';

        if ($this->transactionExists($reference)) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $order->seller_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            return;
        }

        $wallet->decrement('balance', min((float) $wallet->balance, $commission));

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $commission,
            'type' => Transaction::TYPE_ADJUSTMENT,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order commission reversal '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    private function refundBuyerPayment(Order $order): void
    {
        $amount = (float) $order->total;

        if ($amount <= 0 || ! $order->paid_at) {
            return;
        }

        if (! $this->transactionExists($order->order_no)) {
            return;
        }

        $reference = $order->order_no.'-buyer-refund';

        if ($this->transactionExists($reference)) {
            return;
        }

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $order->user_id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $wallet->increment('balance', $amount);

        Transaction::query()->create([
            'user_id' => $order->user_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_REFUND,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order payment refund '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    private function restoreStock(Order $order): void
    {
        if ($order->stock_restored_at !== null) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            Product::query()
                ->whereKey($item->product_id)
                ->increment('stock', $item->qty);
        }

        $order->stock_restored_at = now();
        $order->save();
    }

    private function transactionExists(string $reference): bool
    {
        return Transaction::query()->where('reference', $reference)->exists();
    }
}
