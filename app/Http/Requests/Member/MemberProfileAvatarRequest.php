<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberProfileAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ];
    }
}
