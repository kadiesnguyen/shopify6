<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class ShopInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isShop() === true && $this->user()->shop !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
