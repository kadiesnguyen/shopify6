<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class MemberChangePaymentPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPaymentPassword() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'payment_password' => ['required', 'digits:6', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payment_password.digits' => __('member.payment_password.invalid_digits'),
            'payment_password.confirmed' => __('member.payment_password.mismatch'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! Hash::check((string) $this->input('password'), $this->user()->getRawOriginal('password'))) {
                $validator->errors()->add('password', __('member.profile.current_password_invalid'));
            }
        });
    }
}
