<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Promotion::query()->where('status', Promotion::STATUS_ACTIVE),
            $request,
            searchColumns: ['title'],
            filterable: ['status'],
            sortable: ['created_at', 'title'],
        );

        return PromotionResource::collection($items);
    }
}
