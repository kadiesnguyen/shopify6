<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Request $request): View
    {
        $prefill = mb_substr(trim((string) $request->query('prefill', '')), 0, 5000);

        return view('member.chat.index', compact('prefill'));
    }

    public function messages(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $conversation = $this->chat->findOrCreateForUser($user);

        if ($guestToken = $request->session()->get('chat_guest_token')) {
            $guestConversation = ChatConversation::query()->where('guest_token', $guestToken)->first();
            if ($guestConversation) {
                $conversation = $this->chat->attachGuestToUser($guestConversation, $user);
                $request->session()->forget('chat_guest_token');
            }
        }

        $beforeId = $request->integer('before_id') ?: null;
        $afterId = $request->integer('after_id') ?: null;
        $limit = $request->integer('limit') ?: 40;

        $page = $this->chat->pageMessages(
            $conversation,
            forAdmin: false,
            beforeId: $beforeId,
            afterId: $afterId,
            limit: $limit,
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $page['messages']->map(fn (ChatMessage $m) => $this->messagePayload($m))->values(),
            'has_more' => $page['has_more'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            return response()->json(['message' => __('chat.message_required')], 422);
        }

        $conversation = $this->chat->findOrCreateForUser($user);

        $message = $this->chat->sendMessage(
            $conversation,
            ChatMessage::ROLE_USER,
            $user,
            $data['body'] ?? null,
            $request->file('image'),
        );

        return response()->json([
            'message' => $this->messagePayload($message),
        ]);
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
