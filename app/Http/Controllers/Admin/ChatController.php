<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request): View
    {
        return view('admin.chat.index', [
            'initialFilter' => $request->string('filter', 'all')->toString(),
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $filter = $request->string('filter', 'all')->toString();

        if (! in_array($filter, ['all', 'read', 'unread'], true)) {
            $filter = 'all';
        }

        $conversations = $this->chat->listForAdmin($filter)->map(fn (ChatConversation $c) => $this->conversationPayload($c));

        return response()->json(['conversations' => $conversations]);
    }

    public function show(ChatConversation $conversation): JsonResponse
    {
        $messages = $this->chat
            ->messagesForConversation($conversation, forAdmin: true)
            ->map(fn (ChatMessage $m) => $this->messagePayload($m));

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh(['user', 'latestMessage'])),
            'messages' => $messages,
        ]);
    }

    public function updateDisplayName(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'admin_display_name' => ['nullable', 'string', 'max:120'],
        ]);

        $updated = $this->chat->updateAdminDisplayName(
            $conversation,
            $data['admin_display_name'] ?? null,
        );

        return response()->json([
            'conversation' => $this->conversationPayload($updated->load(['user', 'latestMessage'])),
        ]);
    }

    public function storeMessage(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            return response()->json(['message' => __('chat.message_required')], 422);
        }

        $message = $this->chat->sendMessage(
            $conversation,
            ChatMessage::ROLE_ADMIN,
            $request->user(),
            $data['body'] ?? null,
            $request->file('image'),
        );

        return response()->json([
            'message' => $this->messagePayload($message),
            'conversation' => $this->conversationPayload($conversation->fresh(['user', 'latestMessage'])),
        ]);
    }

    public function destroyMessages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'message_ids' => ['required', 'array', 'min:1'],
            'message_ids.*' => ['integer', 'exists:chat_messages,id'],
        ]);

        $deleted = $this->chat->deleteUserMessages($conversation, $data['message_ids']);

        $messages = $this->chat
            ->messagesForConversation($conversation, forAdmin: false)
            ->map(fn (ChatMessage $m) => $this->messagePayload($m));

        return response()->json([
            'deleted' => $deleted,
            'messages' => $messages,
        ]);
    }

    public function destroyConversations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_ids' => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['integer', 'exists:chat_conversations,id'],
        ]);

        $deleted = $this->chat->deleteConversations($data['conversation_ids']);

        return response()->json([
            'deleted' => $deleted,
        ]);
    }

    /** @return array<string, mixed> */
    private function conversationPayload(ChatConversation $conversation): array
    {
        $latest = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'admin_display_name' => $conversation->admin_display_name,
            'admin_label' => $conversation->adminLabel(),
            'user_name' => $conversation->user?->name,
            'user_email' => $conversation->user?->email,
            'user_phone' => $conversation->user?->phone,
            'guest_label' => $conversation->guest_label,
            'is_guest' => $conversation->user_id === null,
            'unread_count' => $conversation->unreadCountForAdmin(),
            'has_unread' => $conversation->hasUnreadForAdmin(),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'last_message_preview' => $latest?->body ?: ($latest?->image ? __('chat.image_message') : ''),
        ];
    }

    /** @return array<string, mixed> */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_role' => $message->sender_role,
            'body' => $message->body,
            'image_url' => $message->imageUrl(),
            'created_at' => $message->created_at->toIso8601String(),
            'formatted_time' => $message->created_at->format('d/m/Y H:i'),
            'can_delete' => $message->sender_role === ChatMessage::ROLE_USER,
        ];
    }
}
