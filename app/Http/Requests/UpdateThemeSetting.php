<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeSetting extends CoreRequest
{

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
        return [
            'primary_color.*' => 'required',
            'app_name' => 'required',
            'logo' => 'bail|nullable|file|mimes:png,jpg,jpeg,svg,bmp|max:2048',
            'light_logo' => 'bail|nullable|file|mimes:png,jpg,jpeg,svg,bmp|max:2048',
            'login_background' => 'bail|nullable|file|mimes:png,jpg,jpeg,svg,bmp|max:2048',
            'favicon' => 'bail|nullable|file|mimes:png,jpg,jpeg,svg,bmp|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'primary_color.*.required' => __('messages.primaryColorRequired'),
            '*.uploaded' => 'The selected image could not be uploaded. Please use an image smaller than 2 MB.',
            '*.max' => 'The selected image must be smaller than 2 MB.',
            '*.mimes' => 'Please select a PNG, JPG, JPEG, SVG or BMP image.',
        ];
    }

}
