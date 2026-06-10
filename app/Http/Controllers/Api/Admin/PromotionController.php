<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Promotion::query()->with(['user', 'shop']),
            $request,
            searchColumns: ['title'],
            filterable: ['status'],
            sortable: ['created_at', 'title', 'start_date'],
        );

        return PromotionResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'user_id' => ['nullable', 'exists:users,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
        ]);

        $promotion = Promotion::query()->create($data);

        return response()->json(['data' => new PromotionResource($promotion)], 201);
    }

    public function show(Promotion $promotion): PromotionResource
    {
        return new PromotionResource($promotion->load(['user', 'shop']));
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $promotion->update($data);

        return response()->json(['data' => new PromotionResource($promotion->fresh())]);
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $promotion->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        return $this->bulkDelete(Promotion::class, $request);
    }
}
