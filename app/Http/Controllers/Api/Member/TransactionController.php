<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Transaction::query()->where('user_id', auth()->id()),
            $request,
            searchColumns: ['reference', 'description'],
            filterable: ['type', 'status'],
            sortable: ['created_at', 'amount'],
        );

        return TransactionResource::collection($items);
    }
}
