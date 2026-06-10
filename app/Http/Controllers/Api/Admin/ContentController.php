<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function banners(Request $request): JsonResponse
    {
        $items = $this->paginateQuery(
            Banner::query(),
            $request,
            searchColumns: ['image'],
            filterable: ['status'],
            sortable: ['sort_order', 'created_at'],
            defaultSort: 'sort_order',
        );

        return response()->json(['data' => $items]);
    }

    public function pages(Request $request): JsonResponse
    {
        $items = $this->paginateQuery(
            Page::query(),
            $request,
            searchColumns: ['slug', 'type'],
            filterable: ['status', 'type'],
            sortable: ['created_at', 'slug'],
        );

        return response()->json(['data' => $items]);
    }

    public function faqs(Request $request): JsonResponse
    {
        $items = $this->paginateQuery(
            Faq::query(),
            $request,
            searchColumns: [],
            filterable: ['status'],
            sortable: ['sort_order', 'created_at'],
            defaultSort: 'sort_order',
        );

        return response()->json(['data' => $items]);
    }

    public function news(Request $request): JsonResponse
    {
        $items = $this->paginateQuery(
            News::query(),
            $request,
            searchColumns: ['title', 'slug'],
            filterable: ['status'],
            sortable: ['published_at', 'created_at'],
        );

        return response()->json(['data' => $items]);
    }
}
