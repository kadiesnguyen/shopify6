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

    public function toggleFeatured(User $user, ProductDistribution $distribution): RedirectResponse
    {
        abort_unless($distribution->user_id === $user->id, 404);

        $isFeatured = ! $distribution->is_featured;
        $distribution->update([
            'is_featured' => $isFeatured,
            'featured_at' => $isFeatured ? now() : null,
        ]);

        $message = $isFeatured
            ? __('admin.users.distributions.featured_enabled')
            : __('admin.users.distributions.featured_disabled');

        return $this->redirectBack($user, $message);
    }

    public function destroy(User $user, ProductDistribution $distribution): RedirectResponse
    {
        abort_unless($distribution->user_id === $user->id, 404);

        $distribution->delete();

        return $this->redirectBack($user, __('admin.users.distributions.deleted'));
    }

    private function redirectBack(User $user, string $status): RedirectResponse
    {
        $query = array_filter(array_merge(
            request()->only([
                'q', 'role', 'shop_application',
                'dist_q', 'dist_commission_type', 'dist_price_from', 'dist_price_to', 'dist_sort',
            ]),
            ['show_distributions' => $user->id],
        ), fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('admin.users.index', $query)
            ->with('status', $status);
    }
}
