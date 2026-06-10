<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableJson;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasTranslatableJson;

    public const TYPE_ABOUT = 'about';

    public const TYPE_CONTACT = 'contact';

    public const TYPE_POLICY = 'policy';

    public const TYPE_LANDING = 'landing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'slug',
        'type',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'content' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
        ];
    }
}
