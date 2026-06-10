<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('member.profile.show', [
            'user' => auth()->user(),
        ]);
    }
}
