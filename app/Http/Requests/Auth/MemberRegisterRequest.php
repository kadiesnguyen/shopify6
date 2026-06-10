<?php

namespace App\Http\Requests\Auth;

use App\Support\Auth\MemberCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MemberRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('login')) {
            $this->merge(['login' => trim((string) $this->input('login'))]);
        }
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => __('auth_portal.terms_required'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $login = (string) $this->input('login');

            if ($login === '') {
                return;
            }

            if (MemberCredentials::loginExists($login)) {
                $validator->errors()->add('login', __('auth_portal.login_exists'));
            }
        });
    }
}
