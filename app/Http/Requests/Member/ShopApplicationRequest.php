<?php

namespace App\Http\Requests\Member;

use App\Models\ShopApplication;
use App\Support\ShopIndustryRegistry;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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
            'logo' => ['nullable', 'file', 'max:4096', $this->idDocument()],
            'address' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'real_name' => ['required', 'string', 'max:120'],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'id_number' => ['required', 'string', 'max:50'],
            'id_front' => ['required', 'file', 'max:12288', $this->idDocument()],
            'id_back' => ['required', 'file', 'max:12288', $this->idDocument()],
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

    private function idDocument(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                $fail(__('member.shop_application.id_image_invalid'));

                return;
            }

            $extension = strtolower($value->getClientOriginalExtension());
            $mime = strtolower((string) $value->getMimeType());
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
            $dangerous = str_contains($mime, 'php')
                || str_contains($mime, 'html')
                || str_contains($mime, 'javascript')
                || str_starts_with($mime, 'text/');
            $readableImage = str_starts_with($mime, 'image/')
                || in_array($mime, ['application/octet-stream', 'application/heic', 'application/heif', 'image/heic', 'image/heif'], true);

            if ($dangerous || ! in_array($extension, $allowed, true) || ! $readableImage) {
                $fail(__('member.shop_application.id_image_invalid'));
            }
        };
    }

    public function messages(): array
    {
        return [
            'id_front.max' => __('member.shop_application.id_image_too_large'),
            'id_back.max' => __('member.shop_application.id_image_too_large'),
            'logo.max' => __('member.shop_application.logo_too_large'),
            'terms.accepted' => __('member.shop_application.terms_required'),
            'industry_id.required' => __('member.shop_application.industry_required'),
            'business_category_ids.required' => __('member.shop_application.business_categories_required'),
            'shop_description.required' => __('member.shop_application.shop_description_required'),
        ];
    }
}
