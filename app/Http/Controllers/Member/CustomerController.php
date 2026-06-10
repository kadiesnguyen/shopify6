<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('member.customers.index');
    }
}
