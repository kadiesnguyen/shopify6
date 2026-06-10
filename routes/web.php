<?php

use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Auth\MemberRegisterController;
use App\Http\Controllers\Guest\ChatController as GuestChatController;
use App\Http\Controllers\Landing\ContactController;
use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\LocaleController;
use App\Http\Controllers\Landing\NewsController;
use App\Http\Controllers\Landing\PageController;
use App\Http\Controllers\Landing\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('landing.sitemap');

Route::get('/', [HomeController::class, 'index'])->name('landing.home');
Route::get('/tin-tuc', [NewsController::class, 'index'])->name('landing.news.index');
Route::get('/tin-tuc/{slug}', [NewsController::class, 'show'])->name('landing.news.show');
Route::get('/gioi-thieu', [PageController::class, 'about'])->name('landing.about');
Route::get('/lien-he', [ContactController::class, 'create'])->name('landing.contact');
Route::post('/lien-he', [ContactController::class, 'store'])->name('landing.contact.store');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::prefix('chat/guest')->name('guest.chat.')->group(function (): void {
    Route::get('/messages', [GuestChatController::class, 'show'])->name('messages.index');
    Route::post('/messages', [GuestChatController::class, 'store'])->name('messages.store');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [MemberLoginController::class, 'create'])->name('auth.login');
    Route::post('/dang-nhap', [MemberLoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/dang-ky', [MemberRegisterController::class, 'create'])->name('auth.register');
    Route::post('/dang-ky', [MemberRegisterController::class, 'store'])->middleware('throttle:login');
    Route::view('/quen-mat-khau', 'auth.forgot-password')->name('auth.password.request');
});

Route::post('/logout', [MemberLoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout');
