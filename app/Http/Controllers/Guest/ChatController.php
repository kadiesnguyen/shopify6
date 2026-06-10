<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function show(Request $request): JsonResponse
    {
        $token = $this->resolveGuestToken($request);
        $conversation = $this->chat->findOrCreateForGuest(
            $token,
            $request->session()->get('chat_guest_label'),
        );

        $messages = $this->chat
            ->messagesForConversation($conversation, forAdmin: false)
            ->map(fn (ChatMessage $m) => $this->messagePayload($m));

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'guest_label' => ['nullable', 'string', 'max:120'],
        ]);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            return response()->json(['message' => __('chat.message_required')], 422);
        }

        $token = $this->resolveGuestToken($request);

        if (! empty($data['guest_label'])) {
            $request->session()->put('chat_guest_label', trim($data['guest_label']));
        }

        $conversation = $this->chat->findOrCreateForGuest(
            $token,
            $request->session()->get('chat_guest_label'),
        );

        $message = $this->chat->sendMessage(
            $conversation,
            ChatMessage::ROLE_USER,
            null,
            $data['body'] ?? null,
            $request->file('image'),
        );

        return response()->json([
            'message' => $this->messagePayload($message),
        ]);
    }

    private function resolveGuestToken(Request $request): string
    {
        $token = $request->session()->get('chat_guest_token');

        if (! $token) {
            $token = $this->chat->guestTokenFromSession(null);
            $request->session()->put('chat_guest_token', $token);
        }

        return $token;
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
        ];
    }
}
