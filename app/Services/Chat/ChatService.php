<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatService
{
    public function findOrCreateForUser(User $user): ChatConversation
    {
        return ChatConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()],
        );
    }

    public function findOrCreateForGuest(string $guestToken, ?string $guestLabel = null): ChatConversation
    {
        return ChatConversation::query()->firstOrCreate(
            ['guest_token' => $guestToken],
            [
                'guest_label' => $guestLabel,
                'last_message_at' => now(),
            ],
        );
    }

    public function attachGuestToUser(ChatConversation $conversation, User $user): ChatConversation
    {
        if ($conversation->user_id) {
            return $conversation;
        }

        $existing = ChatConversation::query()->where('user_id', $user->id)->first();

        if ($existing && $existing->id !== $conversation->id) {
            ChatMessage::query()
                ->where('conversation_id', $conversation->id)
                ->update(['conversation_id' => $existing->id]);

            $conversation->delete();

            return $existing->fresh();
        }

        $conversation->update([
            'user_id' => $user->id,
            'guest_token' => null,
        ]);

        return $conversation->fresh();
    }

    /** @return Collection<int, ChatConversation> */
    public function listForAdmin(string $filter = 'all'): Collection
    {
        $query = ChatConversation::query()
            ->with(['user', 'latestMessage'])
            ->orderByDesc('last_message_at');

        if ($filter === 'unread') {
            $query->whereHas('messages', function ($q) {
                $q->where('sender_role', ChatMessage::ROLE_USER)
                    ->whereNull('read_by_admin_at');
            });
        } elseif ($filter === 'read') {
            $query->whereDoesntHave('messages', function ($q) {
                $q->where('sender_role', ChatMessage::ROLE_USER)
                    ->whereNull('read_by_admin_at');
            })->whereHas('messages');
        }

        return $query->get();
    }

    /** @return Collection<int, ChatMessage> */
    public function messagesForConversation(ChatConversation $conversation, bool $forAdmin): Collection
    {
        if ($forAdmin) {
            $this->markReadByAdmin($conversation);
        } else {
            $this->markReadByUser($conversation);
        }

        return $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Cursor page of messages (oldest→newest within the page).
     *
     * - no cursor: latest page
     * - before_id: older messages than that id
     * - after_id: newer messages than that id
     *
     * @return array{messages: Collection<int, ChatMessage>, has_more: bool}
     */
    public function pageMessages(
        ChatConversation $conversation,
        bool $forAdmin,
        ?int $beforeId = null,
        ?int $afterId = null,
        int $limit = 40,
    ): array {
        $limit = max(1, min($limit, 100));

        if ($forAdmin) {
            $this->markReadByAdmin($conversation);
        } else {
            $this->markReadByUser($conversation);
        }

        $query = $conversation->messages()->with('sender');

        if ($afterId !== null) {
            $messages = (clone $query)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get();

            return [
                'messages' => $messages,
                'has_more' => false,
            ];
        }

        if ($beforeId !== null) {
            $messages = (clone $query)
                ->where('id', '<', $beforeId)
                ->orderByDesc('id')
                ->limit($limit + 1)
                ->get();
        } else {
            $messages = (clone $query)
                ->orderByDesc('id')
                ->limit($limit + 1)
                ->get();
        }

        $hasMore = $messages->count() > $limit;
        if ($hasMore) {
            $messages = $messages->take($limit);
        }

        return [
            'messages' => $messages->sortBy('id')->values(),
            'has_more' => $hasMore,
        ];
    }

    public function sendMessage(
        ChatConversation $conversation,
        string $role,
        ?User $sender,
        ?string $body,
        ?UploadedFile $image = null,
    ): ChatMessage {
        $imagePath = $image ? $image->store('chat', 'public') : null;

        $message = $conversation->messages()->create([
            'sender_role' => $role,
            'sender_user_id' => $sender?->id,
            'body' => $body ? trim($body) : null,
            'image' => $imagePath,
            'read_by_admin_at' => $role === ChatMessage::ROLE_ADMIN ? now() : null,
            'read_by_user_at' => $role === ChatMessage::ROLE_USER ? now() : null,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        return $message->fresh(['sender']);
    }

    public function markReadByAdmin(ChatConversation $conversation): void
    {
        ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_role', ChatMessage::ROLE_USER)
            ->whereNull('read_by_admin_at')
            ->update(['read_by_admin_at' => now()]);

        $conversation->update(['admin_last_read_at' => now()]);
    }

    public function markReadByUser(ChatConversation $conversation): void
    {
        ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_role', ChatMessage::ROLE_ADMIN)
            ->whereNull('read_by_user_at')
            ->update(['read_by_user_at' => now()]);

        $conversation->update(['user_last_read_at' => now()]);
    }

    public function updateAdminDisplayName(ChatConversation $conversation, ?string $name): ChatConversation
    {
        $conversation->update([
            'admin_display_name' => $name !== null && $name !== '' ? trim($name) : null,
        ]);

        return $conversation->fresh();
    }

    /** @param array<int> $messageIds */
    public function deleteUserMessages(ChatConversation $conversation, array $messageIds): int
    {
        return ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_role', ChatMessage::ROLE_USER)
            ->whereIn('id', $messageIds)
            ->delete();
    }

    /** @param array<int> $conversationIds */
    public function deleteConversations(array $conversationIds): int
    {
        if ($conversationIds === []) {
            return 0;
        }

        $conversations = ChatConversation::query()
            ->whereIn('id', $conversationIds)
            ->get();

        $deleted = 0;

        foreach ($conversations as $conversation) {
            ChatMessage::query()
                ->where('conversation_id', $conversation->id)
                ->delete();

            $conversation->delete();
            $deleted++;
        }

        return $deleted;
    }

    public function guestTokenFromSession(?string $sessionToken): string
    {
        return $sessionToken ?: Str::uuid()->toString();
    }
}
