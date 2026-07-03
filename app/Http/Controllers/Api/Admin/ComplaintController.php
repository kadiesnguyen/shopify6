<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\MemberComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $complaints = MemberComplaint::query()
            ->with('user:id,email,phone,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15);

        return response()->json($complaints);
    }

    public function update(Request $request, MemberComplaint $complaint): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([MemberComplaint::STATUS_PENDING, MemberComplaint::STATUS_RESOLVED])],
        ]);

        $complaint->update($validated);

        return response()->json(['data' => $complaint->fresh()]);
    }

    public function destroy(MemberComplaint $complaint): JsonResponse
    {
        $complaint->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
