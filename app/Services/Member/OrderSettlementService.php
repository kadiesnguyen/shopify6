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
    public function __construct(
        private readonly ProductDistributionService $distributionService,
        private readonly MemberNotificationService $notifications,
    ) {}

    public function removeOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->with(['items', 'productDistribution'])
                ->firstOrFail();

            if ($order->status !== Order::STATUS_CANCELLED) {
                $this->handleCancellation($order, $order->status);
            }

            $order->items()->delete();
            $order->delete();
        });
    }

    public function applyStatusChange(Order $order, string $previousStatus, string $newStatus): Order
    {
        if ($previousStatus === $newStatus) {
            return $order;
        }

        return DB::transaction(function () use ($order, $previousStatus, $newStatus): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->with(['items', 'productDistribution'])->firstOrFail();

            if ($newStatus === Order::STATUS_COMPLETED) {
                $this->creditSellerPurchaseReturn($order);
                $this->creditSellerCommission($order);
                $this->releaseDistribution($order);
                $order->completed_at = now();
            }

            if ($newStatus === Order::STATUS_CANCELLED) {
                $this->handleCancellation($order, $previousStatus);
            }

            $order->status = $newStatus;
            $order->save();

            if ($newStatus === Order::STATUS_PENDING_PAYMENT && $previousStatus !== Order::STATUS_PENDING_PAYMENT) {
                $this->notifications->notifyOrderNeedsPayment($order);
            }

            if ($newStatus === Order::STATUS_COMPLETED && $previousStatus !== Order::STATUS_COMPLETED) {
                $this->notifications->notifyOrderCompleted($order);
            }

            return $order->fresh(['items', 'buyer.wallet', 'seller.wallet']);
        });
    }

    /** @var list<string> */
    public const SELLER_SHIP_CONFIRM_STATUSES = [
        Order::STATUS_PENDING_PAYMENT,
        Order::STATUS_AWAITING_PICKUP,
    ];

    public function confirmPlatformShipping(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->with(['items', 'productDistribution'])
                ->firstOrFail();

            if (! in_array($order->status, self::SELLER_SHIP_CONFIRM_STATUSES, true)) {
                throw new RuntimeException('invalid_status');
            }

            $this->deductSellerProductCost($order);

            $order->status = Order::STATUS_SHIPPED;
            $order->shipped_at = now();
            $order->save();

            return $order->fresh(['items', 'buyer.wallet', 'seller.wallet']);
        });
    }

    public function deductSellerProductCost(Order $order): void
    {
        $amount = (float) $order->purchase_cost;

        if ($amount <= 0 || ! $order->seller_id) {
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

        if (! $wallet || (float) $wallet->balance < $amount) {
            throw new RuntimeException('insufficient_balance');
        }

        $wallet->decrement('balance', $amount);

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_PRODUCT_COST,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order purchase cost '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    public function creditSellerPurchaseReturn(Order $order): void
    {
        $amount = (float) $order->purchase_cost;

        if ($amount <= 0 || ! $order->seller_id) {
            return;
        }

        $reference = $order->order_no.'-seller-purchase-return';

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
            'type' => Transaction::TYPE_PURCHASE_RETURN,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order purchase return '.$order->order_no,
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
            $this->reverseSellerPurchaseReturn($order);
            $this->reverseSellerCommission($order);
        }

        $this->refundSellerProductCost($order);
        $this->refundBuyerPayment($order);
        $this->restoreStock($order);
        $this->releaseDistribution($order);
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

        $wallet = Wallet::query()
            ->where('user_id', $order->seller_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            return;
        }

        $wallet->increment('balance', $amount);

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_REFUND,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order purchase cost refund '.$order->order_no,
            'processed_at' => now(),
        ]);
    }

    private function releaseDistribution(Order $order): void
    {
        $distribution = $order->productDistribution;

        if (! $distribution) {
            return;
        }

        $this->distributionService->release($distribution);
    }

    private function reverseSellerPurchaseReturn(Order $order): void
    {
        $amount = (float) $order->purchase_cost;

        if ($amount <= 0 || ! $order->seller_id) {
            return;
        }

        if (! $this->transactionExists($order->order_no.'-seller-purchase-return')) {
            return;
        }

        $reference = $order->order_no.'-seller-purchase-return-reversal';

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

        $wallet->decrement('balance', min((float) $wallet->balance, $amount));

        Transaction::query()->create([
            'user_id' => $order->seller_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => Transaction::TYPE_ADJUSTMENT,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => $reference,
            'description' => 'Order purchase return reversal '.$order->order_no,
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
