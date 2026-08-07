<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;

class UpdateRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

    protected function prepareForValidation()
    {
        if ($this->filled('reminder_time')) {
            $this->merge([
                'reminder_time' => $this->normalizeTimeInput($this->input('reminder_time')),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'client_name' => 'required',
            'mobile' => ['required', 'regex:/^\+\d{7,15}$/'],
            'client_email' => 'nullable|email:rfc,strict|unique:leads,client_email,'.$this->route('lead_contact').',id,company_id,' . company()->id,
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'nullable|exists:lead_status,id',
            'interest_level' => 'nullable|in:low,medium,high,very_high',
            'deal_size' => 'nullable|numeric|min:0',
            'contact_status' => 'nullable|in:pending,connected,not_connected',
            'contact_status_reason' => 'nullable|required_if:contact_status,not_connected|max:5000',
            'products_services' => 'nullable|string|max:5000',
            'country' => 'nullable|string|max:191',
            'website' => 'nullable|max:191',
            'office' => 'nullable|max:191',
            'followup_date' => 'nullable|date_format:"' . company()->date_format . '"',
            'reminder_time' => 'nullable|date_format:"' . company()->time_format . '"',
            'followup_note' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];

        $rules = $this->customFieldRules($rules);

        return $rules;
    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        $attributes['client_name'] = __('app.name');
        $attributes['client_email'] = __('app.email');
        $attributes['mobile'] = __('modules.lead.mobile');
        $attributes['followup_date'] = __('modules.lead.leadFollowUp');
        $attributes['reminder_time'] = __('modules.timeLogs.startTime');

        return $attributes;
    }

    public function messages()
    {
        return [
            'mobile.regex' => 'Mobile number must be in international format with country code (example: +919876543210).',
        ];
    }

    private function normalizeTimeInput($time)
    {
        if ($time === null) {
            return null;
        }

        $time = trim((string) $time);
        $companyTimeFormat = company()->time_format;

        if ($companyTimeFormat === 'h:i a') {
            return strtolower($time);
        }

        if ($companyTimeFormat === 'h:i A') {
            return strtoupper($time);
        }

        return $time;
    }

}
