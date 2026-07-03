<?php

namespace App\Http\Requests\Member;

use App\Models\ShopApplication;
use App\Support\ShopIndustryRegistry;
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
        $user = $this->user();
        $shop = $user?->shop;
        $isUpgrade = $user?->isShop() && $shop?->isPersonal() === true;
        $industryIds = array_keys(app(ShopIndustryRegistry::class)->industries());

        return [
            'seller_type' => [
                'required',
                Rule::in($isUpgrade
                    ? [ShopApplication::TYPE_BUSINESS]
                    : [ShopApplication::TYPE_PERSONAL, ShopApplication::TYPE_BUSINESS]),
            ],
            'industry_id' => ['required', Rule::in($industryIds)],
            'business_category_ids' => ['required', 'array', 'min:1'],
            'business_category_ids.*' => ['integer', 'exists:categories,id'],
            'shop_name' => ['required', 'string', 'max:120'],
            'shop_description' => ['required', 'string', 'max:2000'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $industryId = (string) $this->input('industry_id');
            $categoryIds = array_map('intval', (array) $this->input('business_category_ids', []));

            if ($industryId === '' || $categoryIds === []) {
                return;
            }

            if (! app(ShopIndustryRegistry::class)->validateSelection($industryId, $categoryIds)) {
                $validator->errors()->add('business_category_ids', __('member.shop_application.business_categories_invalid'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => __('member.shop_application.terms_required'),
            'industry_id.required' => __('member.shop_application.industry_required'),
            'business_category_ids.required' => __('member.shop_application.business_categories_required'),
            'shop_description.required' => __('member.shop_application.shop_description_required'),
        ];
    }
}
