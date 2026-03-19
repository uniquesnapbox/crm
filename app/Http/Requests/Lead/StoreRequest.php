<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;

class StoreRequest extends CoreRequest
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
        $rules = [];

        $rules['client_name'] = 'required';
        $rules['mobile'] = 'required';
        $rules['client_email'] = 'nullable|email:rfc,strict|unique:leads,client_email,null,id,company_id,' . company()->id;
        $rules['assigned_to'] = 'nullable|exists:users,id';
        $rules['country'] = 'nullable|string|max:191';
        $rules['website'] = 'nullable|max:191';
        $rules['office'] = 'nullable|max:191';
        $rules['followup_date'] = 'nullable|date_format:"' . company()->date_format . '"';
        $rules['reminder_time'] = 'nullable|date_format:"' . company()->time_format . '"';
        $rules['followup_note'] = 'nullable|string';
        $rules['latitude'] = 'nullable|numeric';
        $rules['longitude'] = 'nullable|numeric';

        return $this->customFieldRules($rules);

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
