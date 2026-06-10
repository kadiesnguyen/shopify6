<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'excerpt',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function ($query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
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
