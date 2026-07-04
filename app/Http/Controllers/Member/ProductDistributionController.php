<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Services\Member\ProductDistributionService;
use App\Support\ShopIndustryRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProductDistributionController extends Controller
{
    public function __construct(
        private readonly ProductDistributionService $distributionService,
        private readonly ShopIndustryRegistry $industries,
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

    public function store(Request $request): RedirectResponse|JsonResponse
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

        $shop = $user->shop;

        if ($shop && ! $this->industries->shopAllowsProduct($shop, $product)) {
            $message = __('member.products.industry_restricted');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['product_id' => $message]);
        }

        $this->distributionService->distribute($user, $product);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('member.products.distributed_success'),
                'product_id' => $product->id,
                'redirect' => route('member.products.manage.index'),
            ]);
        }

        return redirect()
            ->route('member.products.manage.index')
            ->with('status', __('member.products.distributed_success'));
    }

    public function manage(Request $request): View
    {
        abort_unless(auth()->user()->isShop(), 403);
        $keyword = trim($request->string('q')->toString());

        $distributions = ProductDistribution::query()
            ->where('user_id', auth()->id())
            ->available()
            ->with(['product.category', 'product.shop'])
            ->when($keyword !== '', fn ($query) => $query->whereHas(
                'product',
                fn ($productQuery) => $productQuery->where('name', 'like', "%{$keyword}%"),
            ))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.manage', compact('distributions'));
    }

    public function update(Request $request, ProductDistribution $distribution): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        abort_unless($user->isShop(), 403);
        abort_unless($distribution->user_id === $user->id, 404);

        $validated = $request->validate([
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->distributionService->updateSellingPrice(
                $distribution,
                (float) $validated['selling_price'],
            );
        } catch (RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'below_purchase' => __('member.products.price_below_purchase'),
                'above_market' => __('member.products.price_above_market'),
                default => __('member.products.price_update_failed'),
            };

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['selling_price' => $message]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('member.products.price_updated')]);
        }

        return redirect()
            ->route('member.products.manage.index')
            ->with('status', __('member.products.price_updated'));
    }
}
