<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ShopDashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('member.my.index');
    }
}
