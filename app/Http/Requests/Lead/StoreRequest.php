<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;

class StoreRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

    protected function prepareForValidation()
    {
        if ($this->filled('reminder_time')) {
            $this->merge([
                'reminder_time' => $this->normalizeTimeInput($this->input('reminder_time')),
            ]);
        }

        $mobile = $this->input('mobile');

        if ($this->filled('mobile')) {
            $mobile = $this->normalizeExistingMobile($mobile, $this->input('mobile_country_code'), $this->input('country'));
        }
        else {
            $mobile = $this->buildInternationalMobile($this->input('mobile_local'), $this->input('mobile_country_code'), $this->input('country'));
        }

        $this->merge([
            'mobile' => $mobile,
        ]);
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
        $rules = [];

        $rules['client_name'] = 'required';
        $rules['mobile'] = ['required', 'regex:/^\+\d{7,15}$/'];
        $rules['client_email'] = 'nullable|email:rfc,strict|unique:leads,client_email,null,id,company_id,' . company()->id;
        $rules['assigned_to'] = 'nullable|exists:users,id';
        $rules['status_id'] = 'nullable|exists:lead_status,id';
        $rules['interest_level'] = 'nullable|in:low,medium,high,very_high';
        $rules['deal_size'] = 'nullable|numeric|min:0';
        $rules['contact_status'] = 'nullable|in:pending,connected,not_connected';
        $rules['contact_status_reason'] = 'nullable|required_if:contact_status,not_connected|max:5000';
        $rules['products_services'] = 'nullable|string|max:5000';
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

    private function buildInternationalMobile($localMobile, $countryCode, $countryName = null): ?string
    {
        $digits = $this->normalizeMobileInput($localMobile);

        if ($digits === null || $digits === '') {
            return null;
        }

        $countryCode = $this->normalizeCountryCode($countryCode, $countryName);
        if ($countryCode === null) {
            return null;
        }

        if (str_starts_with($digits, $countryCode)) {
            $digits = substr($digits, strlen($countryCode));
        }

        if ($countryCode === '91') {
            $digits = substr($digits, 0, 10);
        }
        else {
            $digits = substr($digits, 0, 12);
        }

        if ($digits === '') {
            return null;
        }

        return '+' . $countryCode . $digits;
    }

    private function normalizeMobileInput($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\D+/', '', (string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeExistingMobile($mobile, $countryCode, $countryName = null): ?string
    {
        if ($mobile === null) {
            return null;
        }

        $mobile = trim((string) $mobile);

        if ($mobile === '') {
            return null;
        }

        if (str_starts_with($mobile, '+')) {
            $digits = preg_replace('/\D+/', '', substr($mobile, 1));

            return $digits === '' ? null : '+' . $digits;
        }

        return $this->buildInternationalMobile($mobile, $countryCode, $countryName);
    }

    private function normalizeCountryCode($countryCode, $countryName = null): ?string
    {
        $code = preg_replace('/\D+/', '', (string) $countryCode);

        if ($code !== '') {
            return $code;
        }

        if (!$countryName) {
            return '91';
        }

        $countries = collect(countries());
        $country = $countries->first(function ($item) use ($countryName) {
            return strtolower((string) $item->nicename) === strtolower((string) $countryName);
        });

        $code = preg_replace('/\D+/', '', (string) ($country->phonecode ?? ''));

        return $code !== '' ? $code : '91';
    }

}
