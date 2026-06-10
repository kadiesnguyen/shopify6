<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'withdrawal_method_id' => ['required', 'exists:withdrawal_methods,id'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'payout_details' => ['required', 'string', 'max:500'],
        ];
    }
}
