<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class PaymentPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_password' => ['required', 'digits:6', 'confirmed'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_password.digits' => __('member.payment_password.invalid_digits'),
            'payment_password.confirmed' => __('member.payment_password.mismatch'),
        ];
    }
}
