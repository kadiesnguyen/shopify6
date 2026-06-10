<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Api\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = $this->paginateQuery(
            User::query()->withoutAdmins()->with(['roles', 'wallet', 'shop']),
            $request,
            searchColumns: ['name', 'email', 'phone', 'user_code', 'username'],
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
            'role' => ['required', 'in:admin,member'],
        ]);

        $user = User::query()->create([
            ...collect($data)->except('role')->all(),
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole($data['role']);
        Wallet::query()->firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0]);

        return response()->json(['data' => new UserResource($user->load('roles', 'wallet'))], 201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles', 'wallet', 'shop'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'username' => ['sometimes', 'string', 'unique:users,username,'.$user->id],
            'name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['sometimes', 'in:active,inactive,banned'],
            'role' => ['sometimes', 'in:admin,member'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update(collect($data)->except('role')->all());

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json(['data' => new UserResource($user->fresh()->load('roles', 'wallet', 'shop'))]);
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
