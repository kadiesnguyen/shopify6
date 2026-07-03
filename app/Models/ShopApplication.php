<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopApplication extends Model
{
    public const TYPE_PERSONAL = 'personal';

    public const TYPE_BUSINESS = 'business';

    public const KIND_REGISTRATION = 'registration';

    public const KIND_UPGRADE = 'upgrade';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'seller_type',
        'industry_id',
        'business_category_ids',
        'application_kind',
        'shop_name',
        'shop_description',
        'logo',
        'address',
        'country',
        'phone',
        'real_name',
        'referral_code',
        'id_number',
        'id_front',
        'id_back',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'business_category_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documentUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public function isUpgrade(): bool
    {
        return $this->application_kind === self::KIND_UPGRADE;
    }

    public function isRegistration(): bool
    {
        return ($this->application_kind ?? self::KIND_REGISTRATION) === self::KIND_REGISTRATION;
    }
}
