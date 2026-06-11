<?php

namespace App\Http\Requests\Member;

use App\Support\Auth\MemberCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MemberProfileEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditEmail() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (MemberCredentials::isPlaceholderEmail(is_string($value) ? $value : null)) {
                        $fail(__('member.profile.email_invalid'));
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! Hash::check($this->input('current_password'), $this->user()->password)) {
                $validator->errors()->add('current_password', __('member.profile.current_password_invalid'));
            }
        });
    }
}
