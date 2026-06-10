<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'category_id',
        'shop_id',
        'user_id',
        'name',
        'slug',
        'image',
        'description',
        'selling_price',
        'purchase_price',
        'commission',
        'commission_type',
        'stock',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'commission' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(ProductDistribution::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return str_starts_with($this->image, 'images/')
            ? asset($this->image)
            : asset('storage/'.$this->image);
    }
}
