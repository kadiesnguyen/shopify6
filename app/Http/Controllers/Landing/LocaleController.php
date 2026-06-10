<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['vi', 'en'], true)) {
            $request->session()->put('locale', $locale);
        }

        return back();
    }
}
