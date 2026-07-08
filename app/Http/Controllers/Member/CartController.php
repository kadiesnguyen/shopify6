<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Member\CartService;
use App\Services\Member\OrderService;
use App\Models\ShippingAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function index(): View
    {
        $groups = $this->cart->groupedByShop(auth()->user());
        $total = $this->cart->selectedTotal(auth()->user());
        $itemCount = $this->cart->countFor(auth()->user());

        return view('member.cart.index', compact('groups', 'total', 'itemCount'));
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'shop_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        try {
            $this->cart->add(
                auth()->user(),
                $product,
                (int) ($validated['qty'] ?? 1),
                isset($validated['shop_user_id']) ? (int) $validated['shop_user_id'] : null,
            );
        } catch (\RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'cannot_buy_own_shop' => __('member.cart.cannot_buy_own_shop'),
                default => __('member.cart.add_failed'),
            };

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['cart' => $message]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => __('member.cart.added_toast')]);
        }

        $redirect = $request->input('redirect', route('member.cart.index'));

        return redirect($redirect)->with('status', __('member.cart.added'));
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        if ($request->has('quantity')) {
            $this->cart->updateQuantity($cartItem, (int) $request->input('quantity', 1));
        }

        if ($request->has('selected')) {
            $this->cart->toggleSelected($cartItem, (bool) $request->boolean('selected'));
        }

        return back();
    }

    public function selectAll(Request $request): RedirectResponse
    {
        $this->cart->selectAll(auth()->user(), $request->boolean('selected', true));

        return back();
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $this->cart->remove($cartItem);

        return back()->with('status', __('member.cart.removed'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasPaymentPassword()) {
            return redirect()->route('member.payment-password.create', [
                'redirect' => route('member.cart.index'),
            ]);
        }

        if (! ShippingAddress::query()->where('user_id', $user->id)->exists()) {
            return redirect()
                ->route('member.shipping.index', ['redirect' => route('member.cart.index')])
                ->withErrors(['address' => __('member.checkout.address_required')]);
        }

        $items = $this->cart->selectedItems($user);

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => __('member.cart.empty_checkout')]);
        }

        $total = $items->sum(fn (CartItem $item): float => $item->lineTotal());
        $wallet = $user->wallet;

        if (! $wallet || (float) $wallet->balance < $total) {
            return back()->withErrors(['cart' => __('member.checkout.insufficient_balance')]);
        }

        try {
            foreach ($items as $item) {
                $this->orders->placeOrder($user, $item->product, $item->quantity);
                $this->cart->remove($item);
            }
        } catch (\RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'insufficient_stock' => back()->withErrors(['cart' => __('member.checkout.insufficient_stock')]),
                'cannot_buy_own_shop' => back()->withErrors(['cart' => __('member.checkout.cannot_buy_own_shop')]),
                default => back()->withErrors(['cart' => __('member.checkout.insufficient_balance')]),
            };
        }

        return redirect()
            ->route('member.orders.index', ['status' => 'awaiting_pickup'])
            ->with('status', __('member.order_placed'));
    }
}
