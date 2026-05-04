<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;

class QuickUpdateRequest extends CoreRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'field' => 'required|in:client_name,client_email,source_id,category_id,status_id,assigned_to,interest_level,deal_size,contact_status,company_name,website,mobile,office,country,address,products_services',
            'value' => 'nullable',
        ];
    }
}
