<?php

namespace App\Models;

use App\Support\Auth\MemberCredentials;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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

    public function avatarUrl(): ?string
    {
        if (! filled($this->avatar)) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        if (str_starts_with($this->avatar, 'uploads/')) {
            return asset($this->avatar);
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function registeredViaEmail(): bool
    {
        return filled($this->email) && ! MemberCredentials::isPlaceholderEmail($this->email);
    }

    public function registeredViaPhone(): bool
    {
        return filled($this->phone);
    }

    public function canEditPhone(): bool
    {
        return $this->registeredViaEmail();
    }

    public function canEditEmail(): bool
    {
        return $this->registeredViaPhone() && MemberCredentials::isPlaceholderEmail($this->email);
    }

    public function isPhoneVerified(): bool
    {
        return filled($this->phone);
    }

    public function isEmailVerified(): bool
    {
        if ($this->canEditEmail()) {
            return false;
        }

        return $this->email_verified_at !== null;
    }

    public function isNameVerified(): bool
    {
        return filled($this->name);
    }

    public function loginIdentifier(): ?string
    {
        if ($this->registeredViaEmail()) {
            return $this->email;
        }

        if ($this->registeredViaPhone()) {
            return $this->phone;
        }

        return $this->email ?: $this->phone;
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

    public function isShop(): bool
    {
        return $this->hasRole('shop');
    }

    public function adminFormRole(): string
    {
        if ($this->hasRole('admin')) {
            return 'admin';
        }

        if ($this->hasRole('shop')) {
            return $this->shop?->isBusiness()
                ? 'shop_business'
                : 'shop_personal';
        }

        if ($this->hasRole('member')) {
            return 'member';
        }

        return $this->roles->first()?->name ?? 'member';
    }

    /** @return list<string> */
    public static function adminMemberRoleOptions(): array
    {
        return ['member', 'shop_personal', 'shop_business'];
    }

    public static function isAdminShopFormRole(?string $role): bool
    {
        return in_array($role, ['shop', 'shop_personal', 'shop_business'], true);
    }

    public function isMemberOnly(): bool
    {
        return $this->hasRole('member') && ! $this->isShop();
    }

    public function canSelfDistribute(): bool
    {
        return $this->isShop() && ! $this->distribution_locked;
    }

    public function scopeWithoutAdmins($query)
    {
        return $query->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('name', 'admin'));
    }

    public function scopeAdminKeywordSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword): void {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('username', 'like', "%{$keyword}%")
                ->orWhere('user_code', 'like', "%{$keyword}%")
                ->orWhereHas('shop', fn ($shopQuery) => $shopQuery->where('name', 'like', "%{$keyword}%"));
        });
    }
}
