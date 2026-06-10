<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    protected $fillable = [
        'user_id',
        'guest_token',
        'guest_label',
        'admin_display_name',
        'admin_last_read_at',
        'user_last_read_at',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_last_read_at' => 'datetime',
            'user_last_read_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    public function adminLabel(): string
    {
        if ($this->admin_display_name) {
            return $this->admin_display_name;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->guest_label ?? __('chat.guest_user');
    }

    public function hasUnreadForAdmin(): bool
    {
        return $this->messages()
            ->where('sender_role', ChatMessage::ROLE_USER)
            ->whereNull('read_by_admin_at')
            ->exists();
    }

    public function unreadCountForAdmin(): int
    {
        return $this->messages()
            ->where('sender_role', ChatMessage::ROLE_USER)
            ->whereNull('read_by_admin_at')
            ->count();
    }
}
