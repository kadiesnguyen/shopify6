<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'user_code' => ['nullable', 'string', 'max:32', Rule::unique('users', 'user_code')->ignore($userId)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8'],
            'status' => ['required', 'in:active,inactive,banned'],
            'role' => ['required', 'in:admin,member'],
        ];
    }
}
