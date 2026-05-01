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
        return [
            'lead_created_template' => ['nullable', 'string'],
            'lead_created_sender_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:30'],
            'ticket_assigned_staff_template' => ['nullable', 'string'],
            'ticket_assigned_client_template' => ['nullable', 'string'],
            'ticket_resolved_client_template' => ['nullable', 'string'],
            'task_assigned_staff_template' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_created_sender_number.regex' => 'Sender number must contain digits only, without spaces or a plus sign.',
        ];
    }
}
