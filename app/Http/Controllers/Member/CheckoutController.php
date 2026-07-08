<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Services\Member\OrderService;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ProductDistributionService;
use App\Support\Member\CheckoutGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly ProductDistributionService $distributionService,
    ) {}

    public function show(Request $request, Product $product): View|RedirectResponse
    {
        abort_unless(ProductBuyableQuery::isBuyable($product), 404);

        $redirect = CheckoutGate::redirectFor(auth()->user(), $product);

        if ($redirect) {
            return redirect($redirect);
        }

        $shopId = $request->integer('shop_id') ?: null;
        $product->load(['shop', 'category']);
        $unitPrice = $this->distributionService->previewOrderPrice($product, $shopId, auth()->id()) ?? (float) $product->selling_price;
        $product->setAttribute('display_selling_price', $unitPrice);

        $address = ShippingAddress::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->latest()
            ->first();

        $wallet = auth()->user()->wallet;

        return view('member.checkout.show', compact('product', 'address', 'wallet', 'shopId'));
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
            'shop_id' => ['nullable', 'integer'],
        ]);

        $shopId = ($validated['shop_id'] ?? 0) > 0 ? (int) $validated['shop_id'] : null;
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
        $unitPrice = $this->distributionService->previewOrderPrice($product, $shopId, auth()->id()) ?? (float) $product->selling_price;
        $total = $unitPrice * $qty;

        if (! $wallet || $wallet->balance < $total) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => __('member.checkout.insufficient_balance')]);
        }

        try {
            $this->orderService->placeOrder(auth()->user(), $product, $qty, $shopId);
        } catch (\RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'insufficient_stock' => back()
                    ->withInput()
                    ->withErrors(['qty' => __('member.checkout.insufficient_stock')]),
                'cannot_buy_own_shop' => back()
                    ->withInput()
                    ->withErrors(['payment_method' => __('member.checkout.cannot_buy_own_shop')]),
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
