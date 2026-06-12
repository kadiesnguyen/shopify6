<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDistribution extends Model
{
    public const COMMISSION_FIXED = 'fixed';

    public const COMMISSION_PERCENT = 'percent';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RESERVED = 'reserved';

    protected $fillable = [
        'user_id',
        'product_id',
        'selling_price',
        'purchase_price',
        'commission',
        'commission_type',
        'status',
        'is_featured',
        'featured_at',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'commission' => 'decimal:2',
            'is_featured' => 'boolean',
            'featured_at' => 'datetime',
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeFeatured($query)
    {
        return $query
            ->available()
            ->where('is_featured', true);
    }
}
