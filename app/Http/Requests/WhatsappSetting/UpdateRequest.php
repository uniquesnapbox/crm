<?php

namespace App\Http\Requests\WhatsappSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isActive = $this->has('whatsapp_status');

        return [
            'base_url' => [$isActive ? 'required' : 'nullable', 'url'],
            'api_token' => [$isActive ? 'required' : 'nullable', 'string', 'max:255'],
            'default_country_code' => [$isActive ? 'required' : 'nullable', 'regex:/^[0-9]+$/', 'max:10'],
            'test_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:30'],
            'send_whatsapp' => ['nullable', 'array'],
            'send_whatsapp.*' => ['integer', 'exists:email_notification_settings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_country_code.regex' => 'Default country code must contain digits only, without a plus sign.',
            'test_number.regex' => 'Test number must contain digits only, without spaces or a plus sign.',
        ];
    }
}
