<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Support\AppLocale;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('member.settings.index', [
            'user' => auth()->user(),
        ]);
    }

    public function language(): View
    {
        return view('member.settings.language', [
            'locales' => AppLocale::configured(),
            'currentLocale' => AppLocale::display(),
        ]);
    }

    public function bindLogin(): View
    {
        return view('member.settings.bind-login', [
            'user' => auth()->user(),
        ]);
    }

    public function changeAccount(): View
    {
        return view('member.settings.change-account', [
            'user' => auth()->user(),
        ]);
    }
}
