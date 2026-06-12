<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Admin\AdminUserUpdateService;
use App\Services\Api\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly AdminUserUpdateService $userUpdates) {}

    public function index(Request $request)
    {
        $users = $this->paginateQuery(
            User::query()->withoutAdmins()->with(['roles', 'wallet', 'shop']),
            $request,
            searchColumns: ['name', 'email', 'phone', 'username'],
            filterable: ['status'],
            sortable: ['created_at', 'name', 'email'],
        );

        return UserResource::collection($users);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'unique:users,username'],
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['required', 'in:active,inactive,banned'],
            'role' => ['required', 'in:admin,member,shop'],
        ]);

        $user = User::query()->create([
            ...collect($data)->except('role')->all(),
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole($data['role']);
        Wallet::query()->firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0]);

        return response()->json(['data' => new UserResource($user->load('roles', 'wallet', 'shop'))], 201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles', 'wallet', 'shop', 'shippingAddresses'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_if($user->isAdmin(), 404);

        $data = $request->validate([
            'username' => ['sometimes', 'string', Rule::unique('users', 'username')->ignore($user->id)],
            'user_code' => ['nullable', 'string', Rule::unique('users', 'user_code')->ignore($user->id)],
            'name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['sometimes', 'in:active,inactive,banned'],
            'role' => ['sometimes', 'in:admin,member,shop'],
            'shop_name' => ['nullable', 'string', 'max:120'],
            'followers' => ['nullable', 'integer', 'min:0'],
            'credit_score' => ['nullable', 'integer', 'min:0'],
            'star_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'id_number' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'id_front' => ['nullable', 'image', 'max:4096'],
            'id_back' => ['nullable', 'image', 'max:4096'],
            'display_pending_orders' => ['nullable', 'integer', 'min:0'],
            'display_delivering_orders' => ['nullable', 'integer', 'min:0'],
            'display_received_orders' => ['nullable', 'integer', 'min:0'],
            'display_completed_orders' => ['nullable', 'integer', 'min:0'],
            'display_total_income' => ['nullable', 'numeric', 'min:0'],
            'display_balance' => ['nullable', 'numeric', 'min:0'],
            'display_total_sales' => ['nullable', 'numeric', 'min:0'],
            'display_total_profit' => ['nullable', 'numeric', 'min:0'],
            'display_orders_today' => ['nullable', 'integer', 'min:0'],
            'display_sales_today' => ['nullable', 'numeric', 'min:0'],
            'display_profit_today' => ['nullable', 'numeric', 'min:0'],
            'display_visitors_today' => ['nullable', 'integer', 'min:0'],
            'display_visitors_7d' => ['nullable', 'integer', 'min:0'],
            'display_visitors_30d' => ['nullable', 'integer', 'min:0'],
        ]);

        $updated = $this->userUpdates->update($user, $data);

        return response()->json(['data' => new UserResource($updated->load('roles', 'wallet', 'shop', 'shippingAddresses'))]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->id === auth()->id(), 422, 'Cannot delete yourself.');
        $user->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        return $this->bulkDelete(User::class, $request);
    }

    public function export(Request $request, CsvExportService $export)
    {
        $query = User::query()->with('roles');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $export->stream(
            $query,
            ['ID', 'Name', 'Email', 'Status', 'Role'],
            fn (User $u) => [$u->id, $u->name, $u->email, $u->status, $u->roles->first()?->name],
            'users-'.now()->format('Ymd-His').'.csv',
        );
    }
}
