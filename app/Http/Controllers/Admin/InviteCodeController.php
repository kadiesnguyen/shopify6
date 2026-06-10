<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InviteCodeController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        $codes = InviteCode::query()
            ->with(['creator', 'usedByUser'])
            ->when($filter === 'unused', fn ($q) => $q->whereNull('used_by'))
            ->when($filter === 'used', fn ($q) => $q->whereNotNull('used_by'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $showAddModal = $request->boolean('show_add');

        return view('admin.invite-codes.index', compact('codes', 'filter', 'showAddModal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['nullable', 'string', 'max:32', 'unique:invite_codes,code']]);

        InviteCode::query()->create([
            'code' => $request->input('code') ?: strtoupper(Str::random(8)),
            'created_by' => auth()->id(),
            'status' => InviteCode::STATUS_ACTIVE,
        ]);

        return back()->with('status', __('admin.invite_codes.created'));
    }

    public function destroy(InviteCode $inviteCode): RedirectResponse
    {
        $inviteCode->delete();

        return back()->with('status', __('admin.invite_codes.deleted'));
    }
}
