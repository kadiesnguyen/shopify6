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

        $balance = (float) (auth()->user()->wallet?->spendableBalance() ?? 0);

        return view('member.products.distributions', compact('products', 'distributedIds', 'balance', 'sort'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        abort_unless($user->canSelfDistribute(), 403);

        $validated = $request->validate([
            'product_id' => [
                'required_without:product_ids',
                'nullable',
                'integer',
                'exists:products,id',
                Rule::unique('product_distributions', 'product_id')->where('user_id', $user->id),
            ],
            'product_ids' => ['required_without:product_id', 'nullable', 'array', 'min:1'],
            'product_ids.*' => [
                'integer',
                'distinct',
                'exists:products,id',
                Rule::unique('product_distributions', 'product_id')->where('user_id', $user->id),
            ],
        ]);

        $productIds = collect($validated['product_ids'] ?? [$validated['product_id']])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            $message = __('member.products.select_products_first');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['product_ids' => $message]);
        }

        $shop = $user->shop;
        $distributed = 0;
        $skipped = 0;

        foreach ($productIds as $productId) {
            if (ProductDistribution::query()->where('user_id', $user->id)->where('product_id', $productId)->exists()) {
                $skipped++;

                continue;
            }

            $product = Product::query()
                ->where('status', Product::STATUS_ACTIVE)
                ->find($productId);

            if (! $product) {
                $skipped++;

                continue;
            }

            if ($shop && ! $this->industries->shopAllowsProduct($shop, $product)) {
                $skipped++;

                continue;
            }

            $this->distributionService->distribute($user, $product);
            $distributed++;
        }

        if ($distributed === 0) {
            $message = $skipped > 0
                ? __('member.products.already_distributed')
                : __('member.products.distribute_failed');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['product_ids' => $message]);
        }

        $message = $productIds->count() === 1
            ? __('member.products.distributed_success')
            : __('member.products.distributed_batch_success', ['count' => $distributed]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'count' => $distributed,
                'redirect' => route('member.products.manage.index'),
            ]);
        }

        return redirect()
            ->route('member.products.manage.index')
            ->with('status', $message);
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

        $rawPrice = $request->input('selling_price');
        if (is_string($rawPrice)) {
            $request->merge([
                'selling_price' => str_replace(',', '.', preg_replace('/[^\d,.\-]/', '', $rawPrice) ?? $rawPrice),
            ]);
        }

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
