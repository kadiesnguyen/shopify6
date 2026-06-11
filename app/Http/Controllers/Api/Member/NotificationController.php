<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Notification::query()->where('user_id', auth()->id())->bellVisible(),
            $request,
            searchColumns: ['title', 'body'],
            filterable: ['type'],
            sortable: ['created_at'],
        );

        return NotificationResource::collection($items);
    }

    public function markRead(int $notification): JsonResponse
    {
        $model = Notification::query()->findOrFail($notification);
        abort_unless($model->user_id === auth()->id(), 403);
        abort_unless(in_array($model->type, Notification::bellTypes(), true), 404);
        $model->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }
}
