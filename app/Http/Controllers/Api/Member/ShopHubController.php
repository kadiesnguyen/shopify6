<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Member\ShopInfoRequest;
use App\Services\Member\ShopHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopHubController extends Controller
{
    public function __construct(private readonly ShopHubService $shopHub) {}

    public function index(): JsonResponse
    {
        return response()->json($this->shopHub->dashboard(auth()->user()));
    }

    public function menu(): JsonResponse
    {
        return response()->json($this->shopHub->menu(auth()->user()));
    }

    public function rank(): JsonResponse
    {
        return response()->json($this->shopHub->rank(auth()->user()));
    }

    public function info(): JsonResponse
    {
        return response()->json($this->shopHub->info(auth()->user()));
    }

    public function updateInfo(ShopInfoRequest $request): JsonResponse
    {
        $user = auth()->user()->load('shop');
        $shop = $user->shop;
        abort_unless($shop, 403);

        $data = $request->safe()->only(['name', 'description', 'keywords', 'address']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('shops/logos', 'public');
        }

        $shop->update($data);
        $user->update([
            'name' => $request->validated('contact_name'),
            'phone' => $request->validated('phone'),
        ]);

        return response()->json([
            'message' => __('member.shop_hub.info_saved'),
            'data' => $this->shopHub->info($user->fresh(['shop'])),
        ]);
    }

    public function reviews(Request $request): JsonResponse
    {
        $reviews = $this->shopHub->reviews(auth()->user());

        return response()->json([
            'data' => $reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'product_name' => $review->product?->name,
                'user_name' => $review->user?->name,
                'created_at' => $review->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }
}
