<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Member\ProductBuyableQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = ProductBuyableQuery::forPortal()
            ->when($request->string('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->when($request->string('shop'), function ($query, $shop): void {
                $query->whereHas('shop', fn ($q) => $q->where('name', 'like', "%{$shop}%"));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('member.products.index', compact('products'));
    }
}
