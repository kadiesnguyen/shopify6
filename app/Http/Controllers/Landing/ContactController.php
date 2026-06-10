<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\ContactRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        $page = Page::query()
            ->where('slug', 'lien-he')
            ->where('status', Page::STATUS_PUBLISHED)
            ->first();

        return view('landing.contact', compact('page'));
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        return redirect()
            ->route('landing.contact')
            ->with('status', __('landing.contact.success'));
    }
}
