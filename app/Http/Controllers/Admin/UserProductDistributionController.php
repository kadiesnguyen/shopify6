<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDistribution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserProductDistributionController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort(403);
    }

    public function update(Request $request, User $user, ProductDistribution $distribution): RedirectResponse
    {
        abort_unless($distribution->user_id === $user->id, 404);

        $validated = $request->validate([
            'selling_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', Rule::in([
                ProductDistribution::COMMISSION_FIXED,
                ProductDistribution::COMMISSION_PERCENT,
            ])],
        ]);

        $distribution->update($validated);

        return $this->redirectBack($user, __('admin.users.distributions.updated'));
    }

    public function destroy(User $user, ProductDistribution $distribution): RedirectResponse
    {
        abort_unless($distribution->user_id === $user->id, 404);

        $distribution->delete();

        return $this->redirectBack($user, __('admin.users.distributions.deleted'));
    }

    private function redirectBack(User $user, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.users.index', array_merge(
                request()->only(['q', 'role', 'shop_application']),
                ['show_distributions' => $user->id],
            ))
            ->with('status', $status);
    }
}
