<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $page = Page::query()
            ->where('slug', 'gioi-thieu')
            ->where('status', Page::STATUS_PUBLISHED)
            ->first();

        return view('landing.about', compact('page'));
    }
}
