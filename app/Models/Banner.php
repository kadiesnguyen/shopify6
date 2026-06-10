<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableJson;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasTranslatableJson;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'image',
        'link_url',
        'sort_order',
        'status',
        'title',
        'subtitle',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
        ];
    }
}
