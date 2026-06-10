<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Faq;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('landing.home', [
            'banners' => Banner::query()
                ->where('status', Banner::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->get(),
            'faqs' => Faq::query()
                ->where('status', Faq::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
