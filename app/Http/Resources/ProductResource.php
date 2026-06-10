<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'description' => $this->description,
            'selling_price' => (float) $this->selling_price,
            'purchase_price' => (float) $this->purchase_price,
            'commission' => (float) $this->commission,
            'stock' => $this->stock,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'shop' => $this->whenLoaded('shop', fn () => ['id' => $this->shop->id, 'name' => $this->shop->name]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
