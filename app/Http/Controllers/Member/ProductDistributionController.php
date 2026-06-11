<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ProductDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductDistributionController extends Controller
{
    public function __construct(
        private readonly ProductDistributionService $distributionService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        abort_unless(auth()->user()->isShop(), 403);

        $distributedIds = ProductDistribution::query()
            ->where('user_id', auth()->id())
            ->pluck('product_id');

        $sort = $request->string('sort')->toString() ?: 'best';
        $keyword = trim($request->string('q')->toString());

        $products = Product::query()
            ->with(['category', 'shop'])
            ->withCount('orderItems')
            ->where('status', Product::STATUS_ACTIVE)
            ->when($keyword !== '', fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
            ->when($sort === 'old', fn ($query) => $query->oldest())
            ->when($sort === 'new', fn ($query) => $query->latest())
            ->when($sort === 'best', fn ($query) => $query->orderByDesc('order_items_count')->latest())
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('member.products.partials.distribution-cards', [
                    'products' => $products,
                    'distributedIds' => $distributedIds,
                ])->render(),
                'has_more' => $products->hasMorePages(),
                'next_page' => $products->currentPage() + 1,
            ]);
        }

        $balance = (float) (auth()->user()->wallet?->balance ?? 0);

        return view('member.products.distributions', compact('products', 'distributedIds', 'balance', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user->canSelfDistribute(), 403);

        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('product_distributions', 'product_id')->where('user_id', $user->id),
            ],
        ]);

        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->findOrFail($validated['product_id']);

        $this->distributionService->distribute($user, $product);

        return redirect()
            ->route('member.products.distributions.index')
            ->with('status', __('member.products.distributed_success'));
    }

    public function manage(Request $request): View
    {
        abort_unless(auth()->user()->isShop(), 403);
        $keyword = trim($request->string('q')->toString());

        $products = ProductBuyableQuery::forShop(auth()->id())
            ->when($keyword !== '', fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.manage', compact('products'));
    }
}
