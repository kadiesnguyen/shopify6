<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::query()
            ->where('status', Promotion::STATUS_ACTIVE)
            ->latest()
            ->paginate(10);

        return view('member.promotions.index', compact('promotions'));
    }
}
