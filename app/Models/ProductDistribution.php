<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDistribution extends Model
{
    public const COMMISSION_FIXED = 'fixed';

    public const COMMISSION_PERCENT = 'percent';

    protected $fillable = [
        'user_id',
        'product_id',
        'selling_price',
        'purchase_price',
        'commission',
        'commission_type',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'commission' => 'decimal:2',
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
}
