<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class ShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
