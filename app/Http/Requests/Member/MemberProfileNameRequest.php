<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberProfileNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
        ];
    }
}
