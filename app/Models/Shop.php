<?php

namespace App\Models;

use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'id_number',
        'id_front',
        'id_back',
        'address',
        'country',
        'followers',
        'credit_score',
        'star_rating',
        'display_pending_orders',
        'display_delivering_orders',
        'display_received_orders',
        'display_completed_orders',
        'display_total_income',
        'display_balance',
        'display_total_sales',
        'display_total_profit',
        'display_orders_today',
        'display_sales_today',
        'display_profit_today',
        'display_visitors_today',
        'display_visitors_7d',
        'display_visitors_30d',
        'order_status_seen_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'followers' => 'integer',
            'credit_score' => 'integer',
            'star_rating' => 'decimal:1',
            'display_pending_orders' => 'integer',
            'display_delivering_orders' => 'integer',
            'display_received_orders' => 'integer',
            'display_completed_orders' => 'integer',
            'display_total_income' => 'decimal:2',
            'display_balance' => 'decimal:2',
            'display_total_sales' => 'decimal:2',
            'display_total_profit' => 'decimal:2',
            'display_orders_today' => 'integer',
            'display_sales_today' => 'decimal:2',
            'display_profit_today' => 'decimal:2',
            'display_visitors_today' => 'integer',
            'display_visitors_7d' => 'integer',
            'display_visitors_30d' => 'integer',
            'order_status_seen_at' => 'array',
        ];
    }

    public function logoUrl(): ?string
    {
        return $this->assetUrl($this->logo);
    }

    public function displayLogoUrl(): ?string
    {
        return $this->logoUrl() ?? $this->user?->avatarUrl();
    }

    public function documentUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (ShopDocumentStorage::isPrivatePath($path)) {
            $document = ShopDocumentStorage::documentTypeFromPath($path);

            if ($document === null || ! $this->user_id) {
                return null;
            }

            return route('admin.users.documents.show', [
                'user' => $this->user_id,
                'document' => $document,
            ]);
        }

        return $this->assetUrl($path);
    }

    public function resolveDisplayCount(string $status, int $calculated): int
    {
        $column = match ($status) {
            'pending_payment' => 'display_pending_orders',
            'shipped' => 'display_delivering_orders',
            'received' => 'display_received_orders',
            'completed' => 'display_completed_orders',
            default => null,
        };

        if ($column === null || $this->{$column} === null) {
            return $calculated;
        }

        return (int) $this->{$column};
    }

    public function resolveDisplayAmount(?float $calculated, ?string $column): float
    {
        if ($column && $this->{$column} !== null) {
            return (float) $this->{$column};
        }

        return (float) ($calculated ?? 0);
    }

    public function resolveDisplayInt(int $calculated, ?string $column): int
    {
        if ($column && $this->{$column} !== null) {
            return (int) $this->{$column};
        }

        return $calculated;
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
