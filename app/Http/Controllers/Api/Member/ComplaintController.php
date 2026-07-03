<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Models\MemberComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(): JsonResponse
    {
        $complaints = MemberComplaint::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return response()->json($complaints);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $complaint = MemberComplaint::query()->create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'status' => MemberComplaint::STATUS_PENDING,
        ]);

        return response()->json(['data' => $complaint], 201);
    }
}
