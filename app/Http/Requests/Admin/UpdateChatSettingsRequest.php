<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_support_title' => ['nullable', 'string', 'max:120'],
            'chat_support_welcome_message' => ['nullable', 'string', 'max:2000'],
            'chat_support_avatar' => ['nullable', 'image', 'max:2048'],
            'remove_chat_support_avatar' => ['nullable', 'boolean'],
        ];
    }
}
