<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Models\OrderRefundRequest;
use App\Models\ShopSubAccount;
use App\Models\UserPayoutAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopMerchantExtrasController extends Controller
{
    public function subAccounts(): JsonResponse
    {
        $shop = $this->shopOrAbort();

        $accounts = ShopSubAccount::query()
            ->where('shop_id', $shop->id)
            ->latest()
            ->get()
            ->map(fn (ShopSubAccount $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'username' => $item->username,
                'phone' => $item->phone,
                'status' => $item->status,
            ]);

        return response()->json(['data' => $accounts]);
    }

    public function storeSubAccount(Request $request): JsonResponse
    {
        $shop = $this->shopOrAbort();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', Rule::unique('shop_sub_accounts', 'username')->where('shop_id', $shop->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        $account = ShopSubAccount::query()->create([
            ...$data,
            'shop_id' => $shop->id,
            'status' => ShopSubAccount::STATUS_ACTIVE,
        ]);

        return response()->json([
            'message' => __('member.shop_hub.sub_account_created'),
            'data' => ['id' => $account->id, 'name' => $account->name, 'username' => $account->username],
        ], 201);
    }

    public function destroySubAccount(ShopSubAccount $subAccount): JsonResponse
    {
        $shop = $this->shopOrAbort();
        abort_unless($subAccount->shop_id === $shop->id, 403);
        $subAccount->delete();

        return response()->json(['message' => __('member.shop_hub.sub_account_deleted')]);
    }

    public function payoutAccounts(): JsonResponse
    {
        $accounts = auth()->user()->payoutAccounts()->latest()->get()->map(fn (UserPayoutAccount $item) => $this->mapPayoutAccount($item));

        return response()->json(['data' => $accounts]);
    }

    public function storePayoutAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([UserPayoutAccount::TYPE_BANK, UserPayoutAccount::TYPE_CRYPTO])],
            'label' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'crypto_currency' => ['nullable', 'string', 'max:20'],
            'crypto_network' => ['nullable', 'string', 'max:120'],
            'crypto_address' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($data['type'] === UserPayoutAccount::TYPE_BANK) {
            abort_unless(filled($data['bank_name'] ?? null) && filled($data['account_name'] ?? null) && filled($data['account_number'] ?? null), 422, __('member.payout_accounts.bank_fields_required'));
        } else {
            abort_unless(filled($data['crypto_currency'] ?? null) && filled($data['crypto_network'] ?? null) && filled($data['crypto_address'] ?? null), 422, __('member.payout_accounts.crypto_fields_required'));
        }

        $user = auth()->user();

        if ($data['is_default'] ?? false) {
            $user->payoutAccounts()->update(['is_default' => false]);
        }

        $account = $user->payoutAccounts()->create($data);

        return response()->json([
            'message' => __('member.payout_accounts.saved'),
            'data' => $this->mapPayoutAccount($account),
        ], 201);
    }

    public function destroyPayoutAccount(UserPayoutAccount $payoutAccount): JsonResponse
    {
        abort_unless($payoutAccount->user_id === auth()->id(), 403);
        $payoutAccount->delete();

        return response()->json(['message' => __('member.payout_accounts.deleted')]);
    }

    public function refunds(): JsonResponse
    {
        $this->ensureSeller();

        $refunds = OrderRefundRequest::query()
            ->with(['order', 'buyer'])
            ->where('seller_id', auth()->id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $refunds->map(fn (OrderRefundRequest $item) => [
                'id' => $item->id,
                'order_no' => $item->order?->order_no,
                'amount' => (float) $item->amount,
                'reason' => $item->reason,
                'status' => $item->status,
                'buyer_name' => $item->buyer?->name,
                'created_at' => $item->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function storeRefund(Request $request): JsonResponse
    {
        $this->ensureSeller();

        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = \App\Models\Order::query()->findOrFail($data['order_id']);
        abort_unless($order->seller_id === auth()->id(), 403);
        abort_if($order->status === \App\Models\Order::STATUS_CANCELLED, 422, __('member.shop_hub.refund_not_allowed'));

        $exists = OrderRefundRequest::query()
            ->where('order_id', $order->id)
            ->where('status', OrderRefundRequest::STATUS_PENDING)
            ->exists();

        abort_if($exists, 422, __('member.shop_hub.refund_pending_exists'));

        $refund = OrderRefundRequest::query()->create([
            'order_id' => $order->id,
            'seller_id' => auth()->id(),
            'buyer_id' => $order->user_id,
            'amount' => $order->total,
            'reason' => $data['reason'] ?? null,
            'status' => OrderRefundRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => __('member.shop_hub.refund_submitted'),
            'data' => ['id' => $refund->id, 'status' => $refund->status],
        ], 201);
    }

    private function ensureSeller(): void
    {
        abort_unless(auth()->user()->isShop(), 403);
    }

    private function shopOrAbort(): \App\Models\Shop
    {
        $this->ensureSeller();
        $shop = auth()->user()->shop;
        abort_unless($shop, 403);

        return $shop;
    }

    /** @return array<string, mixed> */
    private function mapPayoutAccount(UserPayoutAccount $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'label' => $item->label,
            'bank_name' => $item->bank_name,
            'account_name' => $item->account_name,
            'account_number' => $item->account_number,
            'crypto_currency' => $item->crypto_currency,
            'crypto_network' => $item->crypto_network,
            'crypto_address' => $item->crypto_address,
            'is_default' => $item->is_default,
        ];
    }
}
