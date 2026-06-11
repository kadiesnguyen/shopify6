<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberProfilePhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditPhone() ?? false;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
        ];
    }
}
