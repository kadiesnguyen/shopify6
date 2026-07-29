<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Services\Member\CartService;
use App\Services\Member\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $groups = $this->cart->groupedByShop($user)->map(function ($items, $shopName) {
            return [
                'shop_name' => $shopName,
                'items' => $items->map(fn (CartItem $item) => $this->serializeItem($item))->values(),
            ];
        })->values();

        return response()->json([
            'groups' => $groups,
            'selected_total' => $this->cart->selectedTotal($user),
            'item_count' => $this->cart->countFor($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'shop_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        try {
            $item = $this->cart->add(
                auth()->user(),
                $product,
                (int) ($validated['qty'] ?? 1),
                isset($validated['shop_user_id']) ? (int) $validated['shop_user_id'] : null,
            );
        } catch (\RuntimeException) {
            return response()->json(['message' => 'Unable to add to cart.'], 422);
        }

        return response()->json(['data' => $this->serializeItem($item)], 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        if ($request->has('quantity')) {
            $item = $this->cart->updateQuantity($cartItem, (int) $request->input('quantity', 1));
        } elseif ($request->has('selected')) {
            $this->cart->toggleSelected($cartItem, $request->boolean('selected'));
            $item = $cartItem->fresh(['product', 'distribution.user.shop']);
        } else {
            $item = $cartItem;
        }

        return response()->json(['data' => $this->serializeItem($item)]);
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $this->cart->remove($cartItem);

        return response()->json(['message' => 'Removed.']);
    }

    public function selectAll(Request $request): JsonResponse
    {
        $this->cart->selectAll(auth()->user(), $request->boolean('selected', true));

        return $this->index();
    }

    public function checkout(): JsonResponse
    {
        $user = auth()->user();

        // Same gate as the web checkout flow: fund password must be set first.
        if (! $user->hasPaymentPassword()) {
            return response()->json(['message' => 'Payment password required.'], 422);
        }

        if (! ShippingAddress::query()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Shipping address required.'], 422);
        }

        $items = $this->cart->selectedItems($user);

        if ($items->isEmpty()) {
            return response()->json(['message' => 'No items selected.'], 422);
        }

        $total = $items->sum(fn (CartItem $item): float => $item->lineTotal());
        $wallet = $user->wallet;

        if (! $wallet || ! $wallet->canSpend($total)) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        $orderIds = [];

        try {
            foreach ($items as $item) {
                $order = $this->orders->placeOrder($user, $item->product, $item->quantity);
                $orderIds[] = $order->id;
                $this->cart->remove($item);
            }
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['order_ids' => $orderIds], 201);
    }

    /** @return array<string, mixed> */
    private function serializeItem(CartItem $item): array
    {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'product_image' => $item->product?->imageUrl(),
            'quantity' => $item->quantity,
            'selected' => $item->selected,
            'unit_price' => $item->unitPrice(),
            'line_total' => $item->lineTotal(),
            'shop_name' => $item->shopUser?->shop?->name,
        ];
    }
}
