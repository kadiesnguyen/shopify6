<?php

use App\Http\Controllers\Member\ChatController;
use App\Http\Controllers\Member\CheckoutController;
use App\Http\Controllers\Member\ContractController;
use App\Http\Controllers\Member\CustomerController;
use App\Http\Controllers\Member\HomeController;
use App\Http\Controllers\Member\MyController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\OrderController;
use App\Http\Controllers\Member\PaymentPasswordController;
use App\Http\Controllers\Member\ProductController;
use App\Http\Controllers\Member\ProductDistributionController;
use App\Http\Controllers\Member\SellerOrderController;
use App\Http\Controllers\Member\ShopDashboardController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\PromotionController;
use App\Http\Controllers\Member\ShopApplicationController;
use App\Http\Controllers\Member\ShippingAddressController;
use App\Http\Controllers\Member\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')->name('member.')->middleware(['member'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/distributions', [ProductDistributionController::class, 'index'])->name('products.distributions.index');
    Route::post('/products/distributions', [ProductDistributionController::class, 'store'])->name('products.distributions.store');
    Route::get('/products/manage', [ProductDistributionController::class, 'manage'])->name('products.manage.index');

    Route::get('/shop-dashboard', [ShopDashboardController::class, 'index'])->name('shop-dashboard.index');
    Route::get('/seller/orders', [SellerOrderController::class, 'index'])->name('seller.orders.index');
    Route::get('/products/{product}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/products/{product}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/apply-seller', [ShopApplicationController::class, 'create'])->name('shop-application.create');
    Route::post('/apply-seller', [ShopApplicationController::class, 'store'])->name('shop-application.store');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/my', [MyController::class, 'index'])->name('my.index');
    Route::get('/my/personal', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/my/shipping-address', [ShippingAddressController::class, 'index'])->name('shipping.index');
    Route::get('/my/shipping-address/add', [ShippingAddressController::class, 'create'])->name('shipping.create');
    Route::post('/my/shipping-address', [ShippingAddressController::class, 'store'])->name('shipping.store');
    Route::post('/my/shipping-address/{address}/select', [ShippingAddressController::class, 'select'])->name('shipping.select');
    Route::delete('/my/shipping-address/{address}', [ShippingAddressController::class, 'destroy'])->name('shipping.destroy');

    Route::get('/my/set-payment-password', [PaymentPasswordController::class, 'create'])->name('payment-password.create');
    Route::post('/my/set-payment-password', [PaymentPasswordController::class, 'store'])->name('payment-password.store');

    Route::get('/recharge', [WalletController::class, 'recharge'])->name('wallet.recharge');
    Route::post('/recharge', [WalletController::class, 'storeRecharge'])->name('wallet.recharge.store');
    Route::get('/withdrawal', [WalletController::class, 'withdrawal'])->name('wallet.withdrawal');
    Route::post('/withdrawal', [WalletController::class, 'storeWithdrawal'])->name('wallet.withdrawal.store');
    Route::get('/fund-records', [WalletController::class, 'fundRecords'])->name('wallet.fund-records');

    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/contract', [ContractController::class, 'show'])->name('contract.show');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');
});
