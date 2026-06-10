<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableJson;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasTranslatableJson;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'question',
        'answer',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
        ];
    }
}
