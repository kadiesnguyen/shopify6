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
            'current_payment_password' => ['required', 'digits:6'],
            'payment_password' => ['required', 'digits:6', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_payment_password.digits' => __('member.payment_password.invalid_digits'),
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

            $hash = $this->user()->getRawOriginal('payment_password');

            if (! Hash::check($this->input('current_payment_password'), $hash)) {
                $validator->errors()->add('current_payment_password', __('member.profile.current_payment_password_invalid'));
            }
        });
    }
}
