<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('landing.home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('landing.news.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('landing.about'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('landing.contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ]);

        News::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at'])
            ->each(function (News $article) use ($urls): void {
                $urls->push([
                    'loc' => route('landing.news.show', $article->slug),
                    'lastmod' => $article->updated_at?->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ]);
            });

        $xml = view('landing.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
