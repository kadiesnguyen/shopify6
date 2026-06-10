<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function show(): View
    {
        $page = Page::query()
            ->where('slug', 'gioi-thieu')
            ->where('status', Page::STATUS_PUBLISHED)
            ->first();

        return view('member.contract.show', compact('page'));
    }
}
