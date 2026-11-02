<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'in:fixed,percent'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive,draft'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer', 'exists:product_images,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! is_numeric($this->input('selling_price'))) {
            return;
        }

        $selling = round((float) $this->input('selling_price'), 2);
        $commission = round($selling * 0.30, 2);

        $this->merge([
            'selling_price' => $selling,
            'commission' => $commission,
            'purchase_price' => round($selling - $commission, 2),
            'commission_type' => 'fixed',
        ]);
    }
}
