<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'conversation_id',
        'sender_role',
        'sender_user_id',
        'body',
        'image',
        'read_by_admin_at',
        'read_by_user_at',
    ];

    protected function casts(): array
    {
        return [
            'read_by_admin_at' => 'datetime',
            'read_by_user_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
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
