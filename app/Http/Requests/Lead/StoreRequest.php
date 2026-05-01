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
        $rules = array();

        $rules['client_name'] = 'required';
        $rules['mobile'] = 'required';
        $rules['client_email'] = 'nullable|email:rfc,strict|unique:leads,client_email,null,id,company_id,' . company()->id;
        $rules['assigned_to'] = 'nullable|exists:users,id';
        $rules['status_id'] = 'nullable|exists:lead_status,id';
        $rules['interest_level'] = 'nullable|in:low,medium,high,very_high';
        $rules['deal_size'] = 'nullable|numeric|min:0';
        $rules['contact_status'] = 'nullable|in:pending,connected,not_connected';
        $rules['contact_status_reason'] = 'nullable|required_if:contact_status,not_connected|max:5000';
        $rules['products_services'] = 'nullable|string|max:5000';

        return $this->customFieldRules($rules);

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
