<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recharge_method_id' => ['required', 'exists:recharge_methods,id'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
        ];
    }
}
