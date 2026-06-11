<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Faq;
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
                fn () => Banner::query()
                    ->where('status', Banner::STATUS_ACTIVE)
                    ->orderBy('sort_order')
                    ->get(),
            ),
            'faqs' => CachedModelCollection::remember(
                'landing.home.faqs',
                self::CACHE_SECONDS,
                fn () => Faq::query()
                    ->where('status', Faq::STATUS_ACTIVE)
                    ->orderBy('sort_order')
                    ->get(),
            ),
        ]);
    }
}
