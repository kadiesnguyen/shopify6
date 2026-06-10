<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'code',
        'config',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
