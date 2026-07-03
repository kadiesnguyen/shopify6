<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Reviews require a delivered purchase: return the id of an order that
     * qualifies the user to review the product, or null when none exists.
     */
    public static function qualifyingOrderId(int $userId, int $productId): ?int
    {
        return Order::query()
            ->where('user_id', $userId)
            ->whereIn('status', [Order::STATUS_RECEIVED, Order::STATUS_COMPLETED])
            ->where(fn (Builder $query) => $query
                ->whereHas('productDistribution', fn (Builder $distribution) => $distribution->where('product_id', $productId))
                ->orWhereHas('items', fn (Builder $items) => $items->where('product_id', $productId)))
            ->value('id');
    }
}
