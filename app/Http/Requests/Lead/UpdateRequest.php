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
            'status_id' => 'nullable|exists:lead_status,id',
            'interest_level' => 'nullable|in:low,medium,high,very_high',
            'deal_size' => 'nullable|numeric|min:0',
            'contact_status' => 'nullable|in:pending,connected,not_connected',
            'contact_status_reason' => 'nullable|required_if:contact_status,not_connected|max:5000',
            'products_services' => 'nullable|string|max:5000',
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

        return $attributes;
    }

}
