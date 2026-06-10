<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'shop'])
            ->where('status', Product::STATUS_ACTIVE)
            ->when($request->string('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->when($request->string('shop'), function ($query, $shop): void {
                $query->whereHas('shop', fn ($q) => $q->where('name', 'like', "%{$shop}%"));
            })
            ->latest()
            ->limit(12)
            ->get();

        $banners = Banner::query()
            ->where('status', Banner::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return view('member.home', compact('products', 'banners'));
    }
}
