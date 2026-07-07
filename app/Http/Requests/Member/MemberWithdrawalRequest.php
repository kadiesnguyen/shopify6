<?php

namespace App\Http\Requests\Member;

use App\Models\WithdrawalMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

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
            'currency' => ['nullable', 'string', 'max:20'],
            'network' => ['nullable', 'string', 'max:120'],
            'crypto_address' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'payment_password' => ['required', 'digits:6'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if (! $user->hasPaymentPassword()) {
                $validator->errors()->add('payment_password', __('member.payment_password.required_notice'));

                return;
            }

            $hash = $user->getRawOriginal('payment_password');
            if (! Hash::check((string) $this->input('payment_password'), $hash)) {
                $validator->errors()->add('payment_password', __('member.wallet.withdraw_password_invalid'));
            }

            $method = WithdrawalMethod::query()->find($this->input('withdrawal_method_id'));

            if (! $method || $method->status !== WithdrawalMethod::STATUS_ACTIVE) {
                $validator->errors()->add('withdrawal_method_id', __('member.wallet.method_invalid'));

                return;
            }

            if ($method->type === WithdrawalMethod::TYPE_BANK) {
                foreach (['bank_account_name', 'bank_name', 'bank_account_number'] as $field) {
                    if (! filled($this->input($field))) {
                        $validator->errors()->add($field, __('member.wallet.field_required'));
                    }
                }

                return;
            }

            if (! filled($this->input('currency'))) {
                $validator->errors()->add('currency', __('member.wallet.currency_required'));
            }

            if (! filled($this->input('network'))) {
                $validator->errors()->add('network', __('member.wallet.network_required'));
            }

            if (! filled($this->input('crypto_address'))) {
                $validator->errors()->add('crypto_address', __('member.wallet.crypto_address_required'));
            }
        });
    }
}
