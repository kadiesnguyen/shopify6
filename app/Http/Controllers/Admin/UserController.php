<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\ProductDistribution;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withoutAdmins()
            ->with(['roles', 'shop', 'wallet', 'shopApplications'])
            ->when($request->string('q'), function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')))
            ->when($request->filled('shop_application'), function ($query) use ($request): void {
                $filter = $request->string('shop_application')->toString();

                if ($filter === 'pending') {
                    $query->whereHas('shopApplications', fn ($q) => $q->where('status', 'pending'));
                } elseif ($filter === 'approved') {
                    $query->whereHas('shop');
                } elseif ($filter === 'none') {
                    $query->whereDoesntHave('shop')
                        ->whereDoesntHave('shopApplications', fn ($q) => $q->where('status', 'pending'));
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::query()->where('name', '!=', 'admin')->pluck('name');

        $modalUser = $this->resolveModalUser($request);
        $activeModal = $this->resolveActiveModal($request);

        $distributions = collect();

        if ($activeModal === 'distributions' && $modalUser) {
            $distributions = ProductDistribution::query()
                ->with('product.category')
                ->where('user_id', $modalUser->id)
                ->when($request->string('dist_q'), fn ($q, $search) => $q->whereHas(
                    'product',
                    fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"),
                ))
                ->when($request->filled('dist_commission_type'), fn ($q) => $q->where(
                    'commission_type',
                    $request->string('dist_commission_type'),
                ))
                ->when($request->filled('dist_price_from'), fn ($q) => $q->where(
                    'selling_price',
                    '>=',
                    $request->input('dist_price_from'),
                ))
                ->when($request->filled('dist_price_to'), fn ($q) => $q->where(
                    'selling_price',
                    '<=',
                    $request->input('dist_price_to'),
                ))
                ->when(
                    $request->string('dist_sort')->toString() === 'oldest',
                    fn ($q) => $q->oldest(),
                    fn ($q) => $q->latest(),
                )
                ->paginate(10)
                ->withQueryString();
        }

        return view('admin.users.index', compact('users', 'roles', 'modalUser', 'activeModal', 'distributions'));
    }

    private function resolveModalUser(Request $request): ?User
    {
        $modalId = collect([
            'show_info',
            'show_balance',
            'show_deposit',
            'show_password',
            'show_payment_password',
            'show_distributions',
        ])
            ->map(fn (string $key) => $request->integer($key) ?: null)
            ->first(fn (?int $id) => $id !== null);

        if (! $modalId) {
            return null;
        }

        $user = User::query()
            ->with(['roles', 'shop', 'wallet', 'shippingAddresses', 'shopApplications'])
            ->find($modalId);

        return $user?->isAdmin() ? null : $user;
    }

    private function resolveActiveModal(Request $request): ?string
    {
        return match (true) {
            $request->filled('show_info') => 'info',
            $request->filled('show_balance') => 'balance',
            $request->filled('show_deposit') => 'deposit',
            $request->filled('show_password') => 'password',
            $request->filled('show_payment_password') => 'payment_password',
            $request->filled('show_distributions') => 'distributions',
            default => null,
        };
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roles' => Role::query()->pluck('name'),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'username' => $request->validated('username'),
            'user_code' => $request->validated('user_code'),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
            'status' => $request->validated('status'),
        ]);

        $user->syncRoles([$request->validated('role')]);

        Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        return redirect()->route('admin.users.index')->with('status', __('admin.users.created'));
    }

    public function edit(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        return view('admin.users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->pluck('name'),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $data = $request->safe()->except(['password', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('admin.users.index')->with('status', __('admin.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Cannot delete yourself.']);
        }

        $user->delete();

        return back()->with('status', __('admin.users.deleted'));
    }
}
