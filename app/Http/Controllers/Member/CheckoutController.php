<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Services\Member\OrderService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Member\CheckoutGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function show(Product $product): View|RedirectResponse
    {
        abort_unless(ProductBuyableQuery::isBuyable($product), 404);

        $redirect = CheckoutGate::redirectFor(auth()->user(), $product);

        if ($redirect) {
            return redirect($redirect);
        }

        $product->load(['shop', 'category']);

        $address = ShippingAddress::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->latest()
            ->first();

        $wallet = auth()->user()->wallet;

        return view('member.checkout.show', compact('product', 'address', 'wallet'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(ProductBuyableQuery::isBuyable($product), 404);

        if (! auth()->user()->hasPaymentPassword()) {
            return redirect()->route('member.payment-password.create', [
                'redirect' => CheckoutGate::checkoutUrl($product),
            ]);
        }

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'payment_method' => ['required', 'in:balance,cskh'],
        ]);

        $qty = min($validated['qty'], $product->stock);

        if (! ShippingAddress::query()->where('user_id', auth()->id())->exists()) {
            return redirect()
                ->route('member.shipping.index', ['redirect' => CheckoutGate::checkoutUrl($product)])
                ->withErrors(['address' => __('member.checkout.address_required')]);
        }

        if ($validated['payment_method'] === 'cskh') {
            return redirect()
                ->route('member.contract.show')
                ->with('status', __('member.checkout.contact_support'));
        }

        $wallet = auth()->user()->wallet;
        $total = $product->selling_price * $qty;

        if (! $wallet || $wallet->balance < $total) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => __('member.checkout.insufficient_balance')]);
        }

        try {
            $this->orderService->placeOrder(auth()->user(), $product, $qty);
        } catch (\RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'insufficient_stock' => back()
                    ->withInput()
                    ->withErrors(['qty' => __('member.checkout.insufficient_stock')]),
                default => back()
                    ->withInput()
                    ->withErrors(['payment_method' => __('member.checkout.insufficient_balance')]),
            };
        }

        return redirect()
            ->route('member.orders.index', ['status' => 'awaiting_pickup'])
            ->with('status', __('member.order_placed'));
    }
}
