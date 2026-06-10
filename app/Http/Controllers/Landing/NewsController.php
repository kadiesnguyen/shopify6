<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        $news = News::query()
            ->published()
            ->latest('published_at')
            ->paginate(9);

        return view('landing.news.index', compact('news'));
    }

    public function show(string $slug): View
    {
        $article = News::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('landing.news.show', compact('article'));
    }
}
