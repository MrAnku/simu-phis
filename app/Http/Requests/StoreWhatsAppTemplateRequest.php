<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'temp_name' => 'required|string|max:255',
            'temp_body' => ['required', 'string', 'max:5000', function ($attribute, $value, $fail) {
                $count = substr_count($value, '{{var}}');
                if ($count !== 3) {
                    $fail(__('The Template Body must contain exactly 3 instances of {{var}}.'));
                }
            }],
        ];
    }

    public function messages()
    {
        return [
            'temp_name.required' => 'Template name is required.',
            'temp_name.string' => 'Template name must be a string.',
            'temp_name.max' => 'Template name must not exceed 255 characters.',
            'temp_body.required' => 'Template body is required.',
            'temp_body.string' => 'Template body must be a string.',
            'temp_body.max' => 'Template body must not exceed 5000 characters.',
        ];
    }
}
