<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_AWAITING_PICKUP = 'awaiting_pickup';

    public const STATUS_WAITING_SHIPMENT = 'waiting_shipment';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_AWAITING_PICKUP,
        self::STATUS_WAITING_SHIPMENT,
        self::STATUS_SHIPPED,
        self::STATUS_RECEIVED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'shop_id',
        'seller_id',
        'product_distribution_id',
        'order_no',
        'total',
        'commission',
        'purchase_cost',
        'status',
        'payment_method',
        'notes',
        'paid_at',
        'shipped_at',
        'completed_at',
        'stock_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'commission' => 'decimal:2',
            'purchase_cost' => 'decimal:2',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function productDistribution(): BelongsTo
    {
        return $this->belongsTo(ProductDistribution::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
