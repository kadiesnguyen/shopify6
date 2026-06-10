<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'total' => (float) $this->total,
            'commission' => (float) $this->commission,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'shop' => $this->whenLoaded('shop', fn () => ['id' => $this->shop->id, 'name' => $this->shop->name]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
