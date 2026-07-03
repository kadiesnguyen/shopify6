<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_distribution_id',
        'shop_user_id',
        'quantity',
        'selected',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'selected' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(ProductDistribution::class, 'product_distribution_id');
    }

    public function shopUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_user_id');
    }

    public function unitPrice(): float
    {
        return (float) ($this->distribution?->selling_price ?? $this->product?->selling_price ?? 0);
    }

    public function lineTotal(): float
    {
        return $this->unitPrice() * $this->quantity;
    }
}
