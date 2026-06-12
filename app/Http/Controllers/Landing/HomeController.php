<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\Cache\CachedModelCollection;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const CACHE_SECONDS = 300;

    public function index(): View
    {
        return view('landing.home', [
            'banners' => CachedModelCollection::remember(
                'landing.home.banners',
                self::CACHE_SECONDS,
                Banner::class,
                fn () => Banner::query()
                    ->where('status', Banner::STATUS_ACTIVE)
                    ->orderBy('sort_order')
                    ->get(),
            ),
        ]);
    }
}
