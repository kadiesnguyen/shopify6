<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ShopApplicationRequest;
use App\Models\ShopApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopApplicationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->shop) {
            return redirect()
                ->route('member.home')
                ->with('status', __('member.shop_application.already_has_shop'));
        }

        $pending = ShopApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ShopApplication::STATUS_PENDING)
            ->latest()
            ->first();

        if ($pending) {
            return view('member.shop-application.status', [
                'application' => $pending,
            ]);
        }

        return view('member.shop-application.create');
    }

    public function store(ShopApplicationRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->shop) {
            return redirect()
                ->route('member.home')
                ->with('status', __('member.shop_application.already_has_shop'));
        }

        $hasPending = ShopApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ShopApplication::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return redirect()
                ->route('member.shop-application.create')
                ->with('status', __('member.shop_application.pending_exists'));
        }

        $data = collect($request->validated())->except(['logo', 'id_front', 'id_back', 'terms'])->all();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('shop-applications/logos', 'public');
        }

        $data['id_front'] = $request->file('id_front')->store('shop-applications/id', 'public');
        $data['id_back'] = $request->file('id_back')->store('shop-applications/id', 'public');
        $data['user_id'] = $user->id;
        $data['status'] = ShopApplication::STATUS_PENDING;

        ShopApplication::query()->create($data);

        return redirect()
            ->route('member.shop-application.create')
            ->with('status', __('member.shop_application.submitted'));
    }
}
