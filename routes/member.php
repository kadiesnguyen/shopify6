<?php

use App\Http\Controllers\Member\CartController;
use App\Http\Controllers\Member\CategoryBrowseController;
use App\Http\Controllers\Member\ChatController;
use App\Http\Controllers\Member\CheckoutController;
use App\Http\Controllers\Member\ComplaintController;
use App\Http\Controllers\Member\ContractController;
use App\Http\Controllers\Member\CustomerController;
use App\Http\Controllers\Member\FinancialReportController;
use App\Http\Controllers\Member\HomeController;
use App\Http\Controllers\Member\MyController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\OrderController;
use App\Http\Controllers\Member\PaymentPasswordController;
use App\Http\Controllers\Member\ProductController;
use App\Http\Controllers\Member\ProductDistributionController;
use App\Http\Controllers\Member\ProductSearchSuggestionController;
use App\Http\Controllers\Member\ReviewController;
use App\Http\Controllers\Member\SellerOrderController;
use App\Http\Controllers\Member\SettingsController;
use App\Http\Controllers\Member\ShopDashboardController;
use App\Http\Controllers\Member\ShopHubController;
use App\Http\Controllers\Member\ShopInfoController;
use App\Http\Controllers\Member\SellerRefundController;
use App\Http\Controllers\Member\ShopSubAccountController;
use App\Http\Controllers\Member\PayoutAccountController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\PromotionController;
use App\Http\Controllers\Member\ShopApplicationController;
use App\Http\Controllers\Member\ShippingAddressController;
use App\Http\Controllers\Member\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')->name('member.')->middleware(['member'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/categories', [CategoryBrowseController::class, 'index'])->name('categories.index');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/select-all', [CartController::class, 'selectAll'])->name('cart.select-all');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/distributions', [ProductDistributionController::class, 'index'])->name('products.distributions.index');
    Route::post('/products/distributions', [ProductDistributionController::class, 'store'])->name('products.distributions.store');
    Route::get('/products/manage', [ProductDistributionController::class, 'manage'])->name('products.manage.index');
    Route::patch('/products/manage/{distribution}', [ProductDistributionController::class, 'update'])->name('products.manage.update');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/search/suggestions', ProductSearchSuggestionController::class)->name('search.suggestions');

    Route::get('/shop-dashboard', [ShopDashboardController::class, 'index'])->name('shop-dashboard.index');
    Route::get('/shop-hub', [ShopHubController::class, 'index'])->name('shop-hub.index');
    Route::get('/shop-hub/menu', [ShopHubController::class, 'menu'])->name('shop-hub.menu');
    Route::get('/shop-hub/rank', [ShopHubController::class, 'rank'])->name('shop-hub.rank');
    Route::get('/shop-hub/reviews', [ShopHubController::class, 'reviews'])->name('shop-hub.reviews');
    Route::get('/shop-hub/info', [ShopInfoController::class, 'edit'])->name('shop-hub.info');
    Route::put('/shop-hub/info', [ShopInfoController::class, 'update'])->name('shop-hub.info.update');
    Route::get('/shop-hub/sub-accounts', [ShopSubAccountController::class, 'index'])->name('shop-hub.sub-accounts.index');
    Route::post('/shop-hub/sub-accounts', [ShopSubAccountController::class, 'store'])->name('shop-hub.sub-accounts.store');
    Route::delete('/shop-hub/sub-accounts/{subAccount}', [ShopSubAccountController::class, 'destroy'])->name('shop-hub.sub-accounts.destroy');
    Route::get('/seller/refunds', [SellerRefundController::class, 'index'])->name('seller.refunds.index');
    Route::post('/seller/refunds', [SellerRefundController::class, 'store'])->name('seller.refunds.store');
    Route::get('/seller/orders', [SellerOrderController::class, 'index'])->name('seller.orders.index');
    Route::post('/seller/orders/{order}/confirm-shipping', [SellerOrderController::class, 'confirmShipping'])->name('seller.orders.confirm-shipping');
    Route::get('/products/{product}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/products/{product}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/apply-seller', [ShopApplicationController::class, 'create'])->name('shop-application.create');
    Route::post('/apply-seller', [ShopApplicationController::class, 'store'])->name('shop-application.store');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/my', [MyController::class, 'index'])->name('my.index');
    Route::get('/my/personal', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/my/personal', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/my/personal/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::put('/my/personal/name', [ProfileController::class, 'updateName'])->name('profile.name.update');
    Route::put('/my/personal/phone', [ProfileController::class, 'updatePhone'])->name('profile.phone.update');
    Route::put('/my/personal/email', [ProfileController::class, 'updateEmail'])->name('profile.email.update');
    Route::get('/my/personal/change-password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/my/personal/change-password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/my/shipping-address', [ShippingAddressController::class, 'index'])->name('shipping.index');
    Route::get('/my/shipping-address/add', [ShippingAddressController::class, 'create'])->name('shipping.create');
    Route::post('/my/shipping-address', [ShippingAddressController::class, 'store'])->name('shipping.store');
    Route::post('/my/shipping-address/{address}/select', [ShippingAddressController::class, 'select'])->name('shipping.select');
    Route::delete('/my/shipping-address/{address}', [ShippingAddressController::class, 'destroy'])->name('shipping.destroy');

    Route::get('/my/set-payment-password', [PaymentPasswordController::class, 'create'])->name('payment-password.create');
    Route::post('/my/set-payment-password', [PaymentPasswordController::class, 'store'])->name('payment-password.store');
    Route::get('/my/change-payment-password', [PaymentPasswordController::class, 'edit'])->name('payment-password.edit');
    Route::put('/my/change-payment-password', [PaymentPasswordController::class, 'update'])->name('payment-password.update');

    Route::get('/wallet', [WalletController::class, 'hub'])->name('wallet.hub');
    Route::get('/recharge', [WalletController::class, 'recharge'])->name('wallet.recharge');
    Route::post('/recharge', [WalletController::class, 'storeRecharge'])->name('wallet.recharge.store');
    Route::get('/withdrawal', [WalletController::class, 'withdrawal'])->name('wallet.withdrawal');
    Route::post('/withdrawal', [WalletController::class, 'storeWithdrawal'])->name('wallet.withdrawal.store');
    Route::get('/withdrawal-records', [WalletController::class, 'withdrawalRecords'])->name('wallet.withdrawal-records');
    Route::get('/fund-records', [WalletController::class, 'fundRecords'])->name('wallet.fund-records');
    Route::get('/payout-accounts', [PayoutAccountController::class, 'index'])->name('payout-accounts.index');
    Route::post('/payout-accounts', [PayoutAccountController::class, 'store'])->name('payout-accounts.store');
    Route::delete('/payout-accounts/{payoutAccount}', [PayoutAccountController::class, 'destroy'])->name('payout-accounts.destroy');
    Route::get('/my/financial-report', [FinancialReportController::class, 'index'])->name('financial-report.index');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/language', [SettingsController::class, 'language'])->name('settings.language');
    Route::get('/settings/bind-login', [SettingsController::class, 'bindLogin'])->name('settings.bind-login');
    Route::get('/settings/change-account', [SettingsController::class, 'changeAccount'])->name('settings.change-account');

    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/contract', [ContractController::class, 'show'])->name('contract.show');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');
});
