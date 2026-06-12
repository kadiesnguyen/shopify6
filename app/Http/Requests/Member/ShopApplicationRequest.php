<?php

namespace App\Http\Requests\Member;

use App\Models\ShopApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shop = $this->user()?->shop;
        $isUpgrade = $shop?->isPersonal() === true;

        return [
            'seller_type' => [
                'required',
                Rule::in($isUpgrade
                    ? [ShopApplication::TYPE_BUSINESS]
                    : [ShopApplication::TYPE_PERSONAL, ShopApplication::TYPE_BUSINESS]),
            ],
            'shop_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'address' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'real_name' => ['required', 'string', 'max:120'],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'id_number' => ['required', 'string', 'max:50'],
            'id_front' => ['required', 'image', 'max:4096'],
            'id_back' => ['required', 'image', 'max:4096'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => __('member.shop_application.terms_required'),
        ];
    }
}
