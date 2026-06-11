<?php

namespace App\Http\Requests\Member;

use App\Models\RechargeMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'currency' => ['nullable', 'string', 'max:20'],
            'network' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $method = RechargeMethod::query()->find($this->input('recharge_method_id'));

            if (! $method) {
                return;
            }

            if ($method->status !== RechargeMethod::STATUS_ACTIVE) {
                $validator->errors()->add('recharge_method_id', __('member.wallet.recharge_method_disabled'));

                return;
            }

            if ($method->type === RechargeMethod::TYPE_CRYPTO) {
                if (! filled($this->input('currency'))) {
                    $validator->errors()->add('currency', __('member.wallet.currency_required'));
                }

                if (! filled($this->input('network'))) {
                    $validator->errors()->add('network', __('member.wallet.network_required'));
                }
            }
        });
    }
}
