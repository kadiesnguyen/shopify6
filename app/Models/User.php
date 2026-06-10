<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['username', 'user_code', 'name', 'email', 'phone', 'avatar', 'password', 'payment_password', 'status', 'distribution_locked'])]
#[Hidden(['password', 'payment_password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BANNED = 'banned';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'payment_password' => 'hashed',
            'distribution_locked' => 'boolean',
        ];
    }

    public function hasPaymentPassword(): bool
    {
        return filled($this->getRawOriginal('payment_password'));
    }

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function shopApplications(): HasMany
    {
        return $this->hasMany(ShopApplication::class);
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(ShippingAddress::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productDistributions(): HasMany
    {
        return $this->hasMany(ProductDistribution::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function scopeWithoutAdmins($query)
    {
        return $query->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('name', 'admin'));
    }
}
