<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
                'logo' => $this->shop->displayLogoUrl(),
                'id_number' => $this->shop->id_number,
                'id_front' => $this->shop->documentUrl($this->shop->id_front),
                'id_back' => $this->shop->documentUrl($this->shop->id_back),
                'address' => $this->shop->address,
                'country' => $this->shop->country,
                'followers' => $this->shop->followers,
                'credit_score' => $this->shop->credit_score,
                'star_rating' => $this->shop->star_rating,
                'display_pending_orders' => $this->shop->display_pending_orders,
                'display_delivering_orders' => $this->shop->display_delivering_orders,
                'display_received_orders' => $this->shop->display_received_orders,
                'display_completed_orders' => $this->shop->display_completed_orders,
                'display_total_income' => $this->shop->display_total_income,
                'display_balance' => $this->shop->display_balance,
                'display_total_sales' => $this->shop->display_total_sales,
                'display_total_profit' => $this->shop->display_total_profit,
                'display_orders_today' => $this->shop->display_orders_today,
                'display_sales_today' => $this->shop->display_sales_today,
                'display_profit_today' => $this->shop->display_profit_today,
                'display_visitors_today' => $this->shop->display_visitors_today,
                'display_visitors_7d' => $this->shop->display_visitors_7d,
                'display_visitors_30d' => $this->shop->display_visitors_30d,
            ]),
            'shipping_address' => $this->whenLoaded('shippingAddresses', function () {
                $address = $this->shippingAddresses->sortByDesc('is_default')->first();

                return $address ? [
                    'address_line' => $address->address_line,
                    'country' => $address->country,
                ] : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
