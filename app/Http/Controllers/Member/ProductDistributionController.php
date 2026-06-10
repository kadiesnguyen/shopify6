<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ProductDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductDistributionController extends Controller
{
    public function __construct(
        private readonly ProductDistributionService $distributionService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isShop(), 403);

        $distributedIds = ProductDistribution::query()
            ->where('user_id', auth()->id())
            ->pluck('product_id');

        $products = Product::query()
            ->with(['category', 'shop'])
            ->where('status', Product::STATUS_ACTIVE)
            ->when($request->string('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.distributions', compact('products', 'distributedIds'));
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

        $products = ProductBuyableQuery::forShop(auth()->id())
            ->when($request->string('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.manage', compact('products'));
    }
}
