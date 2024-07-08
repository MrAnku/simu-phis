<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'newPassword.confirmed' => 'The new password and confirmation password do not match.',
            'newPassword.min' => 'The new password must be at least 8 characters.',
        ];
    }
}
