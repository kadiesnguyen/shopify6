<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ShippingAddressRequest;
use App\Models\ShippingAddress;
use App\Support\Auth\AuthenticatedUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = ShippingAddress::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        $redirect = $request->string('redirect')->toString();
        $fromCheckout = $redirect !== '' && str_starts_with($redirect, url('/'));

        return view('member.shipping.index', [
            'addresses' => $addresses,
            'redirect' => $redirect,
            'fromCheckout' => $fromCheckout,
        ]);
    }

    public function create(Request $request): View
    {
        return view('member.shipping.create', [
            'redirect' => $request->string('redirect')->toString(),
        ]);
    }

    public function store(ShippingAddressRequest $request): RedirectResponse
    {
        $user = AuthenticatedUserResolver::fromSession();

        if (! $user) {
            return redirect()->route('auth.login')->withErrors([
                'login' => __('auth.session_stale'),
            ]);
        }

        $data = $request->validated();
        unset($data['redirect']);
        $data['user_id'] = $user->id;
        $data['is_default'] = $data['is_default'] ?? true;

        if (! empty($data['is_default'])) {
            ShippingAddress::query()
                ->where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $address = ShippingAddress::query()->create($data);

        if (! $address->exists) {
            return back()
                ->withInput()
                ->withErrors(['address_line' => __('member.shipping.save_failed')]);
        }

        $redirect = $request->string('redirect')->toString();

        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            return redirect($redirect)->with('status', __('member.shipping.saved'));
        }

        return redirect()->route('member.shipping.index')->with('status', __('member.shipping.saved'));
    }

    public function select(Request $request, ShippingAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        ShippingAddress::query()
            ->where('user_id', auth()->id())
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        $redirect = $request->string('redirect')->toString();

        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            return redirect($redirect)->with('status', __('member.shipping.selected'));
        }

        return redirect()->route('member.shipping.index')->with('status', __('member.shipping.selected'));
    }

    public function destroy(ShippingAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = ShippingAddress::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->first();

            $next?->update(['is_default' => true]);
        }

        return back()->with('status', __('member.shipping.deleted'));
    }
}
