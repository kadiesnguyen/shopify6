<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'user_code' => ['nullable', 'string', 'max:32', Rule::unique('users', 'user_code')->ignore($userId)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8'],
            'status' => ['required', 'in:active,inactive,banned'],
            'role' => ['required', 'in:admin,member,shop'],
            'shop_name' => ['nullable', 'string', 'max:120'],
            'followers' => ['nullable', 'integer', 'min:0'],
            'credit_score' => ['nullable', 'integer', 'min:0'],
            'star_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'id_number' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'id_front' => ['nullable', 'image', 'max:4096'],
            'id_back' => ['nullable', 'image', 'max:4096'],
            'display_pending_orders' => ['nullable', 'integer', 'min:0'],
            'display_delivering_orders' => ['nullable', 'integer', 'min:0'],
            'display_received_orders' => ['nullable', 'integer', 'min:0'],
            'display_completed_orders' => ['nullable', 'integer', 'min:0'],
            'display_total_income' => ['nullable', 'numeric', 'min:0'],
            'display_balance' => ['nullable', 'numeric', 'min:0'],
            'display_total_sales' => ['nullable', 'numeric', 'min:0'],
            'display_total_profit' => ['nullable', 'numeric', 'min:0'],
            'display_orders_today' => ['nullable', 'integer', 'min:0'],
            'display_sales_today' => ['nullable', 'numeric', 'min:0'],
            'display_profit_today' => ['nullable', 'numeric', 'min:0'],
            'display_visitors_today' => ['nullable', 'integer', 'min:0'],
            'display_visitors_7d' => ['nullable', 'integer', 'min:0'],
            'display_visitors_30d' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableIntFields = [
            'display_pending_orders',
            'display_delivering_orders',
            'display_received_orders',
            'display_completed_orders',
            'display_orders_today',
            'display_visitors_today',
            'display_visitors_7d',
            'display_visitors_30d',
        ];

        $nullableDecimalFields = [
            'display_total_income',
            'display_balance',
            'display_total_sales',
            'display_total_profit',
            'display_sales_today',
            'display_profit_today',
        ];

        $merged = [];

        foreach ($nullableIntFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }

        foreach ($nullableDecimalFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
