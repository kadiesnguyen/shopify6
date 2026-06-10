<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDistribution;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductDistributionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->shop, 403);

        $distributions = ProductDistribution::query()
            ->with(['product.category', 'product.shop'])
            ->where('user_id', auth()->id())
            ->when($request->string('q'), function ($query, $search): void {
                $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.distributions', compact('distributions'));
    }

    public function manage(Request $request): View
    {
        abort_unless(auth()->user()->shop, 403);

        $products = Product::query()
            ->with(['category', 'shop'])
            ->where('user_id', auth()->id())
            ->when($request->string('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.manage', compact('products'));
    }
}
