<?php

namespace App\Http\Requests\Admin\Employee;

use App\Models\EmployeeDetails;
use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;
use Illuminate\Validation\Rule;

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
        $detailID = EmployeeDetails::where('user_id', $this->route('employee'))->first();
        $setting = company();
        $employeeIdRule = 'nullable|max:50|unique:employee_details,employee_id,null,id,company_id,' . company()->id;

        if ($detailID) {
            $employeeIdRule = 'nullable|max:50|unique:employee_details,employee_id,' . $detailID->id . ',id,company_id,' . company()->id;
        }

        $rules = [
            'employee_id' => $employeeIdRule,
            'name'  => 'required|max:50',
            'mobile' => 'nullable|max:20',
            'address' => 'nullable|max:2000',
            'country' => 'nullable|exists:countries,id',
            'country_phonecode' => 'nullable|max:10',
            'office_phone' => 'nullable|max:30',
            'website' => 'nullable|url|max:191',
            'hourly_rate' => 'nullable|numeric|min:0',
            'department' => 'nullable',
            'designation' => 'nullable',
            'role' => 'nullable|exists:roles,id',
            'joining_date' => 'nullable',
            'date_of_birth' => 'nullable|date_format:"' . $setting->date_format . '"|before_or_equal:' . now($setting->timezone)->toDateString(),
            'reporting_to' => 'nullable|exists:users,id',
            'notice_period' => 'nullable|integer|min:0|max:365',
            'employee_type' => 'nullable|in:office_staff,sales_staff',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'office_latitude' => 'nullable|numeric|between:-90,90',
            'office_longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius' => 'nullable|integer|min:1|max:100000',
            'image' => 'nullable|image',
        ];

        if (isWorksuite()) {
            $rules['email'] = [
                'required',
                'max:100',
                'email:rfc,strict',
                Rule::unique('users', 'email')
                    ->ignore($this->route('employee'))
                    ->where('company_id', company()->id),
            ];
        }

        if (request()->password != '') {
            $rules['password'] = 'required|min:8|max:50';
        }

        if (request()->telegram_user_id) {
            $rules['telegram_user_id'] = $detailID
                ? 'nullable|unique:users,telegram_user_id,' . $detailID->user_id . ',id,company_id,' . company()->id
                : 'nullable|unique:users,telegram_user_id,null,id,company_id,' . company()->id;
        }

        $rules = $this->customFieldRules($rules);

        return $rules;
    }

    public function messages()
    {
        return [
            'email.unique' => __('messages.employeeEmailAlreadyExists'),
        ];
    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        return $attributes;
    }

}
