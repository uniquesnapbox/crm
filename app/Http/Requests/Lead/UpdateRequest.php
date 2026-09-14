<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Rules\UniqueLeadMobile;
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

        if ($this->input('contact_status') === 'not_connected' && ! $this->filled('contact_status_reason')) {
            $this->merge([
                'contact_status_reason' => 'Call not connected',
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
        $leadId = $this->currentLeadId();

        $rules = [
            'client_name' => 'required|string|max:191',
            'mobile' => [
                'required',
                'regex:/^\+\d{7,15}$/',
                new UniqueLeadMobile(company()->id, $leadId),
            ],
            'client_email' => 'nullable|email:rfc,strict|unique:leads,client_email,' . ($leadId ?? 'NULL') . ',id,company_id,' . company()->id,
            'status_id' => 'nullable|integer|exists:lead_status,id',
            'category_id' => 'nullable|integer|exists:lead_category,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'interest_level' => 'nullable|in:low,medium,high,very_high',
            'deal_size' => 'nullable|numeric|min:0',
            'contact_status' => 'nullable|in:pending,connected,not_connected',
            'contact_status_reason' => 'nullable|string|max:5000',
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

    /**
     * Resolve both the API route's {id} and the web resource route's
     * {lead_contact} parameter. The request class is shared by both routes.
     */
    protected function currentLeadId(): ?int
    {
        $routeId = $this->route('id') ?? $this->route('lead_contact');

        return is_numeric($routeId) ? (int) $routeId : null;
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
