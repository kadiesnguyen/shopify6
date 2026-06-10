<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Wallet::query()->with('user'),
            $request,
            searchColumns: [],
            filterable: ['user_id'],
            sortable: ['balance', 'created_at'],
        );

        return WalletResource::collection($items);
    }
}
