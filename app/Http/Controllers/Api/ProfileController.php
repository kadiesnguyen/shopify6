<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Auth\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(): UserResource
    {
        return new UserResource(
            auth()->user()->load('roles', 'wallet', 'shop'),
        );
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->safe()->except(['password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => new UserResource($user->fresh()->load('roles', 'wallet', 'shop')),
        ]);
    }
}
