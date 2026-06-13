<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InviteCodeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordChangeRequestController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\RechargeMethodController;
use App\Http\Controllers\Admin\RechargeRequestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShopDocumentController;
use App\Http\Controllers\Admin\ShopSearchSuggestionController;
use App\Http\Controllers\Admin\ShopApplicationController;
use App\Http\Controllers\Admin\UserActionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSearchSuggestionController;
use App\Http\Controllers\Admin\UserProductDistributionController;
use App\Http\Controllers\Admin\WithdrawalMethodController;
use App\Http\Controllers\Admin\WithdrawalRequestController;
use App\Http\Controllers\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])->middleware('throttle:login');
    });

    Route::middleware(['admin'])->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

        Route::get('users/search-suggestions', UserSearchSuggestionController::class)->name('users.search-suggestions');
        Route::resource('users', UserController::class);
        Route::get('users/{user}/documents/{document}', [ShopDocumentController::class, 'show'])
            ->whereIn('document', ['id_front', 'id_back'])
            ->name('users.documents.show');
        Route::post('users/{user}/distributions', [UserProductDistributionController::class, 'store'])->name('users.distributions.store');
        Route::patch('users/{user}/distributions/{distribution}', [UserProductDistributionController::class, 'update'])->name('users.distributions.update');
        Route::patch('users/{user}/distributions/{distribution}/toggle-featured', [UserProductDistributionController::class, 'toggleFeatured'])->name('users.distributions.toggle-featured');
        Route::delete('users/{user}/distributions/{distribution}', [UserProductDistributionController::class, 'destroy'])->name('users.distributions.destroy');
        Route::patch('users/{user}/balance', [UserActionController::class, 'updateBalance'])->name('users.balance.update');
        Route::post('users/{user}/deposit', [UserActionController::class, 'deposit'])->name('users.deposit');
        Route::patch('users/{user}/password', [UserActionController::class, 'changePassword'])->name('users.password.update');
        Route::patch('users/{user}/payment-password', [UserActionController::class, 'changePaymentPassword'])->name('users.payment-password.update');
        Route::post('users/{user}/toggle-lock', [UserActionController::class, 'toggleAccountLock'])->name('users.toggle-lock');
        Route::post('users/{user}/toggle-distribution-lock', [UserActionController::class, 'toggleDistributionLock'])->name('users.toggle-distribution-lock');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('promotions', PromotionController::class)->except(['show']);

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/shops/search-suggestions', ShopSearchSuggestionController::class)->name('shops.search-suggestions');
        Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/invite-codes', [InviteCodeController::class, 'index'])->name('invite-codes.index');
        Route::post('/invite-codes', [InviteCodeController::class, 'store'])->name('invite-codes.store');
        Route::delete('/invite-codes/{inviteCode}', [InviteCodeController::class, 'destroy'])->name('invite-codes.destroy');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::patch('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        Route::get('/recharge-methods', [RechargeMethodController::class, 'index'])->name('recharge-methods.index');
        Route::post('/recharge-methods', [RechargeMethodController::class, 'store'])->name('recharge-methods.store');
        Route::patch('/recharge-methods/{rechargeMethod}', [RechargeMethodController::class, 'update'])->name('recharge-methods.update');
        Route::patch('/recharge-methods/{rechargeMethod}/toggle-status', [RechargeMethodController::class, 'toggleStatus'])->name('recharge-methods.toggle-status');
        Route::delete('/recharge-methods/{rechargeMethod}', [RechargeMethodController::class, 'destroy'])->name('recharge-methods.destroy');

        Route::get('/withdrawal-methods', [WithdrawalMethodController::class, 'index'])->name('withdrawal-methods.index');
        Route::post('/withdrawal-methods', [WithdrawalMethodController::class, 'store'])->name('withdrawal-methods.store');
        Route::patch('/withdrawal-methods/{withdrawalMethod}', [WithdrawalMethodController::class, 'update'])->name('withdrawal-methods.update');
        Route::patch('/withdrawal-methods/{withdrawalMethod}/toggle-status', [WithdrawalMethodController::class, 'toggleStatus'])->name('withdrawal-methods.toggle-status');
        Route::delete('/withdrawal-methods/{withdrawalMethod}', [WithdrawalMethodController::class, 'destroy'])->name('withdrawal-methods.destroy');

        Route::get('/shop-applications', [ShopApplicationController::class, 'index'])->name('shop-applications.index');
        Route::post('/shop-applications/{shopApplication}/approve', [ShopApplicationController::class, 'approve'])->name('shop-applications.approve');
        Route::post('/shop-applications/{shopApplication}/reject', [ShopApplicationController::class, 'reject'])->name('shop-applications.reject');
        Route::delete('/shop-applications/{shopApplication}', [ShopApplicationController::class, 'destroy'])->name('shop-applications.destroy');

        Route::get('/recharge-requests', [RechargeRequestController::class, 'index'])->name('recharge-requests.index');
        Route::post('/recharge-requests/{rechargeRequest}/approve', [RechargeRequestController::class, 'approve'])->name('recharge-requests.approve');
        Route::post('/recharge-requests/{rechargeRequest}/reject', [RechargeRequestController::class, 'reject'])->name('recharge-requests.reject');

        Route::get('/withdrawal-requests', [WithdrawalRequestController::class, 'index'])->name('withdrawal-requests.index');
        Route::post('/withdrawal-requests/{withdrawalRequest}/approve', [WithdrawalRequestController::class, 'approve'])->name('withdrawal-requests.approve');
        Route::post('/withdrawal-requests/{withdrawalRequest}/reject', [WithdrawalRequestController::class, 'reject'])->name('withdrawal-requests.reject');

        Route::get('/password-change-requests', [PasswordChangeRequestController::class, 'index'])->name('password-change-requests.index');
        Route::post('/password-change-requests/{passwordChangeRequest}/approve', [PasswordChangeRequestController::class, 'approve'])->name('password-change-requests.approve');
        Route::post('/password-change-requests/{passwordChangeRequest}/reject', [PasswordChangeRequestController::class, 'reject'])->name('password-change-requests.reject');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat/settings', [ChatController::class, 'updateSettings'])->name('chat.settings.update');
        Route::get('/chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');
        Route::delete('/chat/conversations', [ChatController::class, 'destroyConversations'])->name('chat.conversations.destroy');
        Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
        Route::patch('/chat/conversations/{conversation}/display-name', [ChatController::class, 'updateDisplayName'])->name('chat.display-name');
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
        Route::delete('/chat/conversations/{conversation}/messages', [ChatController::class, 'destroyMessages'])->name('chat.messages.destroy');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/cms-images', [SettingsController::class, 'uploadCmsImage'])->name('settings.cms-images');
    });
});
