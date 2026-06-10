<?php

namespace App\Http\Requests\Auth;

use App\Support\Auth\PersistentLogin;

class AdminLoginRequest extends LoginRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        PersistentLogin::configureRememberDuration();

        parent::authenticate();
    }
}
