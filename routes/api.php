<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ContentController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Api\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Api\Member\ProductController as MemberProductController;
use App\Http\Controllers\Api\Member\NotificationController as MemberNotificationController;
use App\Http\Controllers\Api\Member\OrderController as MemberOrderController;
use App\Http\Controllers\Api\Member\PromotionController as MemberPromotionController;
use App\Http\Controllers\Api\Member\TransactionController as MemberTransactionController;
use App\Http\Controllers\Api\Member\WalletController as MemberWalletController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::middleware('member.api')->prefix('member')->name('api.member.')->group(function (): void {
        Route::get('/dashboard', [MemberDashboardController::class, 'index']);
        Route::get('/products/{product}', [MemberProductController::class, 'show'])->name('products.show');
        Route::get('/wallet', [MemberWalletController::class, 'show']);
        Route::post('/wallet/recharge', [MemberWalletController::class, 'recharge']);
        Route::get('/orders', [MemberOrderController::class, 'index']);
        Route::get('/orders/{order}', [MemberOrderController::class, 'show']);
        Route::get('/transactions', [MemberTransactionController::class, 'index']);
        Route::get('/promotions', [MemberPromotionController::class, 'index']);
        Route::get('/notifications', [MemberNotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [MemberNotificationController::class, 'markRead']);
    });

    Route::middleware('admin.api')->prefix('admin')->name('api.admin.')->group(function (): void {
        Route::get('/users/export', [AdminUserController::class, 'export']);
        Route::post('/users/bulk', [AdminUserController::class, 'bulk']);
        Route::apiResource('users', AdminUserController::class);

        Route::get('/products/export', [AdminProductController::class, 'export']);
        Route::post('/products/bulk', [AdminProductController::class, 'bulk']);
        Route::apiResource('products', AdminProductController::class);

        Route::post('/categories/bulk', [AdminCategoryController::class, 'bulk']);
        Route::apiResource('categories', AdminCategoryController::class);

        Route::get('/orders/export', [AdminOrderController::class, 'export']);
        Route::post('/orders/bulk', [AdminOrderController::class, 'bulk']);
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{order}', [AdminOrderController::class, 'update']);
        Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy']);

        Route::get('/wallets', [AdminWalletController::class, 'index']);
        Route::get('/transactions/export', [AdminTransactionController::class, 'export']);
        Route::get('/transactions', [AdminTransactionController::class, 'index']);

        Route::post('/promotions/bulk', [AdminPromotionController::class, 'bulk']);
        Route::apiResource('promotions', AdminPromotionController::class);

        Route::get('/content/banners', [ContentController::class, 'banners']);
        Route::get('/content/pages', [ContentController::class, 'pages']);
        Route::get('/content/faqs', [ContentController::class, 'faqs']);
        Route::get('/content/news', [ContentController::class, 'news']);

        Route::apiResource('languages', LanguageController::class)->except(['show']);
    });
});
