<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;

class UpdateRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

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
            'mobile' => 'required',
            'client_email' => 'nullable|email:rfc,strict|unique:leads,client_email,'.$this->route('lead_contact').',id,company_id,' . company()->id,
            'assigned_to' => 'nullable|exists:users,id',
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
        $attributes['mobile'] = __('app.mobile');
        $attributes['followup_date'] = __('modules.lead.leadFollowUp');
        $attributes['reminder_time'] = __('modules.timeLogs.startTime');

        return $attributes;
    }

}
