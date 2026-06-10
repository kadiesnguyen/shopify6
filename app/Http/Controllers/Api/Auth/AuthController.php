<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('roles', 'wallet')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        Role::firstOrCreate(['name' => 'member']);

        $user = User::query()->create([
            'username' => $request->validated('username'),
            'user_code' => 'U'.str_pad((string) (User::query()->count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
            'status' => 'active',
        ]);

        $user->assignRole('member');

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('roles', 'wallet')),
        ], 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Password reset link sent.'
                : __($status),
        ], $status === Password::RESET_LINK_SENT ? 200 : 422);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        return response()->json([
            'message' => $status === Password::PASSWORD_RESET ? 'Password reset.' : __($status),
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }
}
