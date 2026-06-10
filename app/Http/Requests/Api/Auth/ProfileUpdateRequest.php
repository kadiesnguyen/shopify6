<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
