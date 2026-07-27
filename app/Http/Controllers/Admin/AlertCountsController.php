<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAlertCountsService;
use Illuminate\Http\JsonResponse;

class AlertCountsController extends Controller
{
    public function __invoke(AdminAlertCountsService $alerts): JsonResponse
    {
        return response()->json($alerts->counts());
    }
}
