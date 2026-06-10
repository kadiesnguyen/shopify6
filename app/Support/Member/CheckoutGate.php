<?php

namespace App\Support\Member;

use App\Models\Product;
use App\Models\User;

class CheckoutGate
{
    public static function checkoutUrl(Product $product): string
    {
        return route('member.checkout.show', $product);
    }

    public static function redirectFor(User $user, Product $product): ?string
    {
        if (! $user->hasPaymentPassword()) {
            return route('member.payment-password.create', [
                'redirect' => self::checkoutUrl($product),
            ]);
        }

        return null;
    }

    public static function canOpenCheckout(User $user): bool
    {
        return $user->hasPaymentPassword();
    }

    public static function canPlaceOrder(User $user): bool
    {
        return $user->hasPaymentPassword()
            && $user->shippingAddresses()->exists();
    }
}
