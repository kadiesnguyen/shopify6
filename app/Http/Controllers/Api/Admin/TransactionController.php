<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\Api\CsvExportService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Transaction::query()->with('user'),
            $request,
            searchColumns: ['reference', 'description'],
            filterable: ['type', 'status', 'user_id'],
            sortable: ['created_at', 'amount'],
        );

        return TransactionResource::collection($items);
    }

    public function export(Request $request, CsvExportService $export)
    {
        $query = Transaction::query();

        foreach (['type', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $export->stream(
            $query,
            ['ID', 'User', 'Amount', 'Type', 'Status'],
            fn (Transaction $t) => [$t->id, $t->user_id, $t->amount, $t->type, $t->status],
            'transactions-'.now()->format('Ymd-His').'.csv',
        );
    }
}
