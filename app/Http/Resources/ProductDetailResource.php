<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['product']->id,
            'name' => $this->resource['name'],
            'slug' => $this->resource['product']->slug,
            'image_url' => $this->resource['image_url'],
            'images' => $this->resource['images'] ?? [],
            'description' => $this->resource['description'],
            'purchase_price' => $this->resource['purchase_price'],
            'selling_price' => $this->resource['selling_price'],
            'profit' => $this->resource['profit'],
            'stock' => $this->resource['stock'],
            'is_recommended' => $this->resource['is_recommended'],
            'sales_count' => $this->resource['sales_count'] ?? 0,
            'category' => $this->resource['category'],
            'shop' => $this->resource['shop'],
            'status' => $this->resource['product']->status,
        ];
    }
}
