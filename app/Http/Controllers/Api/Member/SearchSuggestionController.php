<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Member\ProductSearchSuggestionController;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request)
    {
        return app(ProductSearchSuggestionController::class)($request);
    }
}
